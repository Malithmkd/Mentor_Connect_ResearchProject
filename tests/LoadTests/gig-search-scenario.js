/**
 * MentorConnect — Gig Search Scenario Module
 *
 * Simulates a freelancer browsing the gig marketplace:
 * 1. Visit the gig listing page
 * 2. Search by keyword
 * 3. Filter by experience level
 * 4. Filter by skills
 * 5. View a gig detail page
 *
 * Exported as a module for use in load-test.js
 *
 * Usage:
 *   import { gigSearchScenario } from './gig-search-scenario.js';
 *   gigSearchScenario(BASE_URL, gigListTimeTrend, errorRateMetric);
 */

import http from 'k6/http';
import { check, sleep } from 'k6';

// ─── Search Terms ─────────────────────────────────────────────────────────────

const SEARCH_TERMS = [
    'Laravel',
    'Python',
    'Machine Learning',
    'System Design',
    'React',
    'DevOps',
    'AWS',
    'Career Coaching',
    'UI UX',
    '',  // empty search (all gigs)
];

const EXPERIENCE_LEVELS = ['beginner', 'intermediate', 'advanced', ''];

const SORT_OPTIONS = ['', 'price_asc', 'price_desc', 'rating'];

// ─── Gig Search Scenario ──────────────────────────────────────────────────────

export function gigSearchScenario(baseUrl, gigListTimeTrend, errorRateMetric) {
    const randomSearch      = SEARCH_TERMS[Math.floor(Math.random() * SEARCH_TERMS.length)];
    const randomExperience  = EXPERIENCE_LEVELS[Math.floor(Math.random() * EXPERIENCE_LEVELS.length)];
    const randomSort        = SORT_OPTIONS[Math.floor(Math.random() * SORT_OPTIONS.length)];

    // ── Step 1: Landing page ────────────────────────────────────────────────
    const homeResponse = http.get(`${baseUrl}/`, {
        headers: { 'Accept': 'text/html', 'Cache-Control': 'no-cache' },
    });

    check(homeResponse, {
        'home page returns 200': (r) => r.status === 200,
        'home page loads within 500ms': (r) => r.timings.duration < 500,
    });

    sleep(0.5);

    // ── Step 2: Gig listing page ────────────────────────────────────────────
    const listParams = new URLSearchParams();
    if (randomSearch)     listParams.set('search', randomSearch);
    if (randomExperience) listParams.set('experience', randomExperience);
    if (randomSort)       listParams.set('sort', randomSort);

    const listUrl      = `${baseUrl}/mentors?${listParams.toString()}`;
    const listResponse = http.get(listUrl, {
        headers: { 'Accept': 'text/html' },
    });

    const listCheck = check(listResponse, {
        'gig list returns 200': (r) => r.status === 200,
        'gig list loads within 400ms': (r) => r.timings.duration < 400,
        'response is not empty': (r) => r.body.length > 500,
    });

    if (gigListTimeTrend) gigListTimeTrend.add(listResponse.timings.duration);
    if (!listCheck) errorRateMetric.add(1);

    sleep(1);

    // ── Step 3: Paginated results ────────────────────────────────────────────
    const page2Params = new URLSearchParams(listParams);
    page2Params.set('page', '2');

    const page2Response = http.get(`${baseUrl}/mentors?${page2Params.toString()}`, {
        headers: { 'Accept': 'text/html' },
    });

    check(page2Response, {
        'page 2 returns 200': (r) => r.status === 200 || r.status === 404, // 404 ok if only 1 page
        'page 2 loads within 400ms': (r) => r.timings.duration < 400,
    });

    sleep(0.5);

    // ── Step 4: Extract a gig slug and view detail page ──────────────────────
    const slugMatch = listResponse.body.match(/\/mentors\/([a-z0-9-]+[a-z0-9]{6,})/);

    if (slugMatch && slugMatch[1]) {
        const gigSlug      = slugMatch[1];
        const gigDetailUrl = `${baseUrl}/mentors/${gigSlug}`;

        const detailResponse = http.get(gigDetailUrl, {
            headers: { 'Accept': 'text/html' },
        });

        check(detailResponse, {
            'gig detail returns 200': (r) => r.status === 200,
            'gig detail loads within 500ms': (r) => r.timings.duration < 500,
            'gig detail contains price': (r) => r.body.includes('Rs '),
        });
    }

    sleep(1);

    // ── Step 5: Price range filter ───────────────────────────────────────────
    const priceParams = new URLSearchParams();
    priceParams.set('min_price', '500');
    priceParams.set('max_price', '3000');

    const priceResponse = http.get(`${baseUrl}/mentors?${priceParams.toString()}`, {
        headers: { 'Accept': 'text/html' },
    });

    check(priceResponse, {
        'price filter returns 200': (r) => r.status === 200,
        'price filter loads within 400ms': (r) => r.timings.duration < 400,
    });

    sleep(0.5);
}

// ─── Stand-alone Smoke Test ───────────────────────────────────────────────────

export const options = {
    scenarios: {
        gig_search_smoke: {
            executor: 'ramping-vus',
            stages: [
                { duration: '30s', target: 10 },
                { duration: '1m', target: 50 },
                { duration: '30s', target: 0 },
            ],
        },
    },
    thresholds: {
        'http_req_duration': ['p(95)<500'],
        'http_req_failed': ['rate<0.01'],
    },
};

/**
 * Default entry point when running this file directly:
 *   k6 run tests/LoadTests/gig-search-scenario.js \
 *       --env BASE_URL=http://localhost:8000
 */
export default function () {
    const baseUrl = __ENV.BASE_URL || 'http://localhost:8000';
    gigSearchScenario(baseUrl, null, { add: () => {} });
    sleep(1);
}
