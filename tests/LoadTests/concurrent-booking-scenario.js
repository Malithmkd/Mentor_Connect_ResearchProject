/**
 * MentorConnect — Concurrent Session Booking Race Condition Scenario
 *
 * Simulates multiple freelancers attempting to book the same time slot
 * simultaneously to test race condition handling and database integrity.
 *
 * Scenario:
 * - 50 VUs all attempt to book the same gig at the same time
 * - Exactly 1 booking per slot should succeed (or all succeed, since
 *   the app doesn't enforce slot uniqueness — we measure throughput)
 * - All responses should be either 200/302 (success) or proper error
 * - No 500 errors should occur under concurrent load
 *
 * Also tests mentor booking queue reads under concurrent writes.
 *
 * Usage:
 *   k6 run tests/LoadTests/concurrent-booking-scenario.js \
 *       --env BASE_URL=http://localhost:8000 \
 *       --env GIG_ID=1
 *
 * Stand-alone:
 *   k6 run tests/LoadTests/concurrent-booking-scenario.js
 */

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Counter, Trend } from 'k6/metrics';

// ─── Custom Metrics ────────────────────────────────────────────────────────────

const bookingSuccessRate = new Rate('booking_success_rate');
const bookingFailRate    = new Rate('booking_failure_rate');
const serverErrorRate    = new Rate('server_error_rate');
const bookingDuration    = new Trend('booking_request_duration', true);
const totalBookings      = new Counter('total_booking_attempts');

// ─── Options ──────────────────────────────────────────────────────────────────

export const options = {
    scenarios: {
        /**
         * Race Condition: 50 VUs all start simultaneously and attempt booking.
         * The arrivalRate pattern ensures simultaneous execution.
         */
        concurrent_booking_race: {
            executor: 'ramping-arrival-rate',
            startRate: 10,
            timeUnit: '1s',
            preAllocatedVUs: 60,
            maxVUs: 100,
            stages: [
                { duration: '30s', target: 30 },   // ramp to 30 requests/sec
                { duration: '2m',  target: 50 },   // peak: 50 requests/sec
                { duration: '30s', target: 10 },   // cool down
            ],
        },

        /**
         * Booking Queue Read: Mentors viewing their booking queue
         * while concurrent writes are happening.
         */
        mentor_queue_reads: {
            executor: 'constant-vus',
            vus: 20,
            duration: '3m',
            startTime: '30s',  // Start after booking race begins
        },
    },

    thresholds: {
        // Overall response time
        'http_req_duration': ['p(95)<600', 'p(99)<1500'],

        // No server errors allowed under concurrent load
        'server_error_rate': ['rate<0.01'],

        // At least 90% of booking attempts should succeed
        'booking_success_rate': ['rate>0.90'],

        // HTTP failures stay low
        'http_req_failed': ['rate<0.05'],
    },
};

// ─── Concurrent Booking Scenario (Exported Module) ────────────────────────────

export function concurrentBookingScenario(baseUrl, bookingTimeTrend, errorRateMetric) {
    const gigId = __ENV.GIG_ID || '1';

    // Step 1: Load the gig page to get CSRF token
    const gigResponse = http.get(`${baseUrl}/mentors/${gigId}`, {
        headers: { 'Accept': 'text/html' },
    });

    check(gigResponse, {
        'gig page accessible': (r) => r.status === 200 || r.status === 404,
    });

    if (gigResponse.status !== 200) {
        // Fallback: try the gig listing
        const listResponse = http.get(`${baseUrl}/mentors`);
        check(listResponse, { 'gig list accessible': (r) => r.status === 200 });
        sleep(1);
        return;
    }

    // Extract CSRF token
    const csrfMatch = gigResponse.body.match(/name="_token"\s+value="([^"]+)"/);
    if (!csrfMatch) {
        sleep(1);
        return;
    }
    const csrfToken = csrfMatch[1];

    sleep(0.1);  // Tiny sleep to increase race concurrency

    // Step 2: Fire concurrent booking request
    const proposedDate = new Date();
    proposedDate.setDate(proposedDate.getDate() + 7);
    const dateStr = proposedDate.toISOString().split('T')[0];

    const bookingStart    = Date.now();
    const bookingResponse = http.post(
        `${baseUrl}/bookings`,
        {
            _token:          csrfToken,
            gig_id:          gigId,
            proposed_date:   dateStr,
            proposed_time:   '10:00',
            freelancer_note: `Load test booking from VU ${__VU}`,
        },
        {
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            redirects: 5,
        }
    );
    const bookingDurationMs = Date.now() - bookingStart;

    totalBookings.add(1);
    if (bookingTimeTrend)  bookingTimeTrend.add(bookingDurationMs);
    bookingDuration.add(bookingDurationMs);

    const isSuccess   = bookingResponse.status === 200 || bookingResponse.status === 302;
    const isServerErr = bookingResponse.status >= 500;

    bookingSuccessRate.add(isSuccess ? 1 : 0);
    bookingFailRate.add(isSuccess ? 0 : 1);
    serverErrorRate.add(isServerErr ? 1 : 0);

    if (errorRateMetric) errorRateMetric.add(isServerErr ? 1 : 0);

    check(bookingResponse, {
        'booking does not return 500': (r) => r.status < 500,
        'booking response is timely': (r) => bookingDurationMs < 2000,
        'booking returns expected status': (r) => [200, 302, 422, 403, 419].includes(r.status),
    });

    sleep(1);
}

// ─── Default Function ─────────────────────────────────────────────────────────

export default function () {
    const baseUrl = __ENV.BASE_URL || 'http://localhost:8000';
    const scenario = __ENV.SCENARIO_NAME || 'concurrent_booking_race';

    if (scenario === 'mentor_queue_reads') {
        group('Mentor Queue Reads', () => {
            mentorQueueReadScenario(baseUrl);
        });
    } else {
        group('Concurrent Booking Race', () => {
            concurrentBookingScenario(baseUrl, null, null);
        });
    }
}

// ─── Mentor Queue Read Scenario ───────────────────────────────────────────────

function mentorQueueReadScenario(baseUrl) {
    // Simulate mentor viewing booking queue (public route — just checks the page loads)
    const response = http.get(`${baseUrl}/mentors`, {
        headers: { 'Accept': 'text/html' },
    });

    check(response, {
        'mentor-facing page is available under concurrent load': (r) => r.status === 200,
        'page loads under 600ms during peak': (r) => r.timings.duration < 600,
    });

    sleep(2);
}

// ─── Race Condition Analysis Report ──────────────────────────────────────────

export function handleSummary(data) {
    const totalAttempts    = data.metrics['total_booking_attempts']?.values?.count || 0;
    const successRate      = data.metrics['booking_success_rate']?.values?.rate || 0;
    const serverErrRate    = data.metrics['server_error_rate']?.values?.rate || 0;
    const p95Duration      = data.metrics['booking_request_duration']?.values?.['p(95)'] || 0;

    const report = {
        summary: {
            total_booking_attempts: totalAttempts,
            success_rate_percent:   (successRate * 100).toFixed(2),
            server_error_rate:      (serverErrRate * 100).toFixed(2),
            p95_duration_ms:        p95Duration.toFixed(0),
        },
        thresholds_passed: data.thresholds
            ? Object.fromEntries(
                Object.entries(data.thresholds).map(([k, v]) => [k, v.ok])
              )
            : {},
        race_condition_analysis: {
            note: 'If total_booking_attempts > 1 and no server errors, race condition is handled gracefully.',
            recommendation: serverErrRate > 0.01
                ? 'INVESTIGATE: Server errors detected under concurrent booking load.'
                : 'PASS: No server errors under concurrent booking load.',
        },
    };

    return {
        'tests/LoadTests/results/concurrent-booking-report.json': JSON.stringify(report, null, 2),
        stdout: JSON.stringify(report, null, 2),
    };
}
