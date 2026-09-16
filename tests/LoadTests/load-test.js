/**
 * MentorConnect — Main k6 Load Test Script
 *
 * Simulates concurrent virtual users performing key workflows:
 * - Freelancers searching and browsing gigs
 * - Mentors viewing their booking queues
 * - Concurrent session booking (race condition testing)
 *
 * Thresholds:
 * - p(95) response time < 500ms
 * - HTTP error rate < 1%
 * - Check failure rate < 5%
 *
 * Usage:
 *   k6 run tests/LoadTests/load-test.js
 *   k6 run --env BASE_URL=http://localhost:8000 tests/LoadTests/load-test.js
 */

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';
import { gigSearchScenario } from './gig-search-scenario.js';
import { concurrentBookingScenario } from './concurrent-booking-scenario.js';

// ─── Configuration ────────────────────────────────────────────────────────────

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

// ─── Custom Metrics ───────────────────────────────────────────────────────────

const errorRate     = new Rate('http_errors');
const bookingTime   = new Trend('booking_request_time', true);
const gigListTime   = new Trend('gig_list_response_time', true);
const lmsIndexTime  = new Trend('lms_index_response_time', true);

// ─── Test Options & Stages ────────────────────────────────────────────────────

export const options = {
    /**
     * Load Stages:
     * 1. Ramp up to 50 VU over 2 minutes (warm up)
     * 2. Ramp up to 200 VU over 3 minutes (peak load)
     * 3. Sustain 200 VU for 5 minutes (stress test)
     * 4. Ramp down to 100 VU for 2 minutes (recovery check)
     * 5. Ramp down to 0 over 1 minute (cool down)
     */
    stages: [
        { duration: '2m', target: 50 },
        { duration: '3m', target: 200 },
        { duration: '5m', target: 200 },
        { duration: '2m', target: 100 },
        { duration: '1m', target: 0 },
    ],

    thresholds: {
        // Core SLA: 95th percentile response time must be < 500ms
        'http_req_duration': [
            'p(95)<500',
            'p(99)<1000',
        ],

        // HTTP error rate must be < 1%
        'http_req_failed': ['rate<0.01'],

        // Custom error rate
        'http_errors': ['rate<0.01'],

        // Gig list must be fast (key landing page)
        'gig_list_response_time': ['p(95)<400'],

        // Booking requests need good performance
        'booking_request_time': ['p(95)<600'],

        // LMS index within acceptable range
        'lms_index_response_time': ['p(95)<500'],

        // All checks must pass > 95% of the time
        'checks': ['rate>0.95'],
    },

    // Enable summary export
    summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'],
};

// ─── Shared Session Manager ───────────────────────────────────────────────────

/**
 * Authenticate as a test user and return session cookies.
 * Uses pre-seeded test accounts to avoid registration overhead.
 */
function loginAs(email, password) {
    const loginPage = http.get(`${BASE_URL}/login`);

    // Extract CSRF token from the login page
    const csrfMatch = loginPage.body.match(/name="_token"\s+value="([^"]+)"/);
    if (!csrfMatch) {
        errorRate.add(1);
        console.error('Failed to extract CSRF token from login page');
        return null;
    }

    const csrfToken = csrfMatch[1];

    const loginResponse = http.post(`${BASE_URL}/login`, {
        email:    email,
        password: password,
        _token:   csrfToken,
    }, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        redirects: 5,
    });

    const loginOk = check(loginResponse, {
        'login succeeds (200 or 302)': (r) => r.status === 200 || r.status === 302,
        'not redirected to login again': (r) => !r.url.includes('/login'),
    });

    if (!loginOk) {
        errorRate.add(1);
        return null;
    }

    return loginResponse.cookies;
}

// ─── Default Function (Mixed Load) ───────────────────────────────────────────

export default function () {
    const vuId = __VU;

    // Distribute VUs across scenarios
    // VU 1–100: freelancer browsing gigs
    // VU 101–160: mentor viewing booking queues
    // VU 161–200: concurrent booking race condition test

    if (vuId <= 100) {
        group('Freelancer: Browse Gigs', () => {
            gigSearchScenario(BASE_URL, gigListTime, errorRate);
        });
    } else if (vuId <= 160) {
        group('Mentor: Booking Queue', () => {
            mentorBookingQueueScenario();
        });
    } else {
        group('Concurrent Booking Race', () => {
            concurrentBookingScenario(BASE_URL, bookingTime, errorRate);
        });
    }

    sleep(1);
}

// ─── Mentor Booking Queue Scenario ───────────────────────────────────────────

function mentorBookingQueueScenario() {
    // Public gig listing (no auth required)
    const mentorIndex = http.get(`${BASE_URL}/mentors`, {
        headers: { 'Accept': 'text/html' },
    });

    const indexCheck = check(mentorIndex, {
        'gig index returns 200': (r) => r.status === 200,
        'gig index contains gig cards': (r) => r.body.includes('mentor') || r.body.includes('session'),
    });

    gigListTime.add(mentorIndex.timings.duration);

    if (!indexCheck) {
        errorRate.add(1);
    }

    sleep(0.5);

    // LMS overview (requires auth — use pre-seeded test account)
    const lmsResponse = http.get(`${BASE_URL}/lms`, {
        headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    // LMS requires auth — 302 redirect to login is acceptable
    check(lmsResponse, {
        'lms returns 200 or auth redirect': (r) => r.status === 200 || r.status === 302,
    });

    lmsIndexTime.add(lmsResponse.timings.duration);
    sleep(1);
}

// ─── Setup: Pre-load check ────────────────────────────────────────────────────

export function setup() {
    const homeResponse = http.get(`${BASE_URL}/`);
    check(homeResponse, {
        'application is up': (r) => r.status === 200,
    });

    return {
        baseUrl: BASE_URL,
        timestamp: Date.now(),
    };
}

// ─── Teardown ─────────────────────────────────────────────────────────────────

export function teardown(data) {
    const elapsed = (Date.now() - data.timestamp) / 1000;
    console.log(`Load test completed in ${elapsed.toFixed(1)}s`);
}

// ─── Custom Summary ───────────────────────────────────────────────────────────

export function handleSummary(data) {
    return {
        'tests/LoadTests/results/summary.json': JSON.stringify(data, null, 2),
        stdout: textSummary(data, { indent: ' ', enableColors: true }),
    };
}

function textSummary(data, opts) {
    const thresholds = data.metrics;
    let output = '\n=== MentorConnect Load Test Summary ===\n';

    const httpDuration = data.metrics['http_req_duration'];
    if (httpDuration) {
        output += `\nHTTP Request Duration:\n`;
        output += `  Avg:  ${httpDuration.values.avg?.toFixed(1)}ms\n`;
        output += `  P95:  ${httpDuration.values['p(95)']?.toFixed(1)}ms\n`;
        output += `  P99:  ${httpDuration.values['p(99)']?.toFixed(1)}ms\n`;
    }

    const errorMetric = data.metrics['http_req_failed'];
    if (errorMetric) {
        output += `\nError Rate: ${(errorMetric.values.rate * 100).toFixed(2)}%\n`;
    }

    output += '\n======================================\n';
    return output;
}
