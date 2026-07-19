/**
 * K6 Load Test Script — AUREX API
 * Versi 2.0 — Dioptimalkan untuk Item #4 Prioritas Tinggi
 *
 * Simulasi concurrent user untuk mengukur performa endpoint utama.
 * Termasuk endpoint baru: GDPR data export, consent, retention policy.
 *
 * Usage:
 *   k6 run tests/k6/load-test.js
 *   k6 run --vus 50 --duration 60s tests/k6/load-test.js
 *   k6 run --vus 100 --duration 120s tests/k6/load-test.js  (Target production)
 *
 * Options:
 *   --vus          Virtual users (default: 10)
 *   --duration     Test duration (default: 30s)
 *   -e API_URL=... Set custom API URL (default: http://localhost:8000/api/v1)
 *
 * Prerequisites:
 *   Install k6: https://k6.io/docs/getting-started/installation/
 *   Backend running: docker compose up -d
 */

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// ─── Custom Metrics ──────────────────────────────────

const failRate = new Rate('failed_requests');
const loginTrend = new Trend('login_duration_ms');
const analyzeTrend = new Trend('analyze_duration_ms');
const historyTrend = new Trend('history_duration_ms');
const exportTrend = new Trend('export_duration_ms');
const healthTrend = new Trend('health_duration_ms');

// ─── Configuration ───────────────────────────────────

const BASE_URL = __ENV.API_URL || 'http://localhost:8000/api/v1';
const DEFAULT_EMAIL = `loadtest_${__VU}@aurex.app`;  // Unique email per VU
const DEFAULT_PASSWORD = 'LoadTest@123';

export const options = {
  stages: [
    { duration: '10s', target: 10 },   // Ramp up ke 10 users
    { duration: '30s', target: 10 },   // Maintain 10 users
    { duration: '10s', target: 20 },   // Ramp up ke 20 users (stress test)
    { duration: '20s', target: 20 },   // Maintain 20 users
    { duration: '10s', target: 0 },    // Ramp down
  ],
  thresholds: {
    failed_requests: ['rate<0.05'],          // Maks 5% failure
    http_req_duration: ['p(95)<5000'],       // 95% request < 5 detik (termasuk upload)
    http_req_duration: ['avg<1500'],         // Rata-rata < 1.5 detik
    login_duration_ms: ['p(95)<2000'],       // Login < 2 detik
    analyze_duration_ms: ['p(95)<8000'],     // Analyze < 8 detik (termasuk AI processing)
    history_duration_ms: ['p(95)<1000'],     // History < 1 detik
    health_duration_ms: ['p(95)<500'],       // Health check < 500ms
  },
  // Jangan timeout pada setup — register bisa lambat
  setupTimeout: '30s',
  // Teardown timeout
  teardownTimeout: '10s',
};

// ─── Setup — Registrasi user test per VU ─────────────
// Setiap virtual user mendaftar dengan email unik
// untuk menghindari konflik registrasi

export function setup() {
  const testUsers = [];

  // Register satu user sebagai sample (yang lain akan register di default())
  const res = http.post(`${BASE_URL}/register`, {
    name: 'Load Test User',
    email: 'loadtest_base@aurex.app',
    password: DEFAULT_PASSWORD,
    password_confirmation: DEFAULT_PASSWORD,
  });

  const token = res.json('access_token');
  return {
    token: token,
    registered: res.status === 200 || res.status === 422, // 422 = already exists
  };
}

// ─── Main Test ───────────────────────────────────────

export default function (data) {
  const token = data.token;
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`,
  };

  // ── Group: Authentication ──────────────────────────
  group('Authentication', () => {
    // Login dengan user yang sudah terdaftar
    const loginRes = http.post(`${BASE_URL}/login`, {
      email: 'loadtest_base@aurex.app',
      password: DEFAULT_PASSWORD,
    }, { headers: { 'Content-Type': 'application/json' } });

    loginTrend.add(loginRes.timings.duration);
    check(loginRes, {
      'login success': (r) => r.status === 200,
      'login has token': (r) => r.json('access_token') !== undefined,
    });
    failRate.add(loginRes.status !== 200);
    sleep(0.5);
  });

  // ── Group: User Profile ────────────────────────────
  group('User Profile', () => {
    const userRes = http.get(`${BASE_URL}/user`, { headers });
    check(userRes, {
      'user info success': (r) => r.status === 200,
      'user has email': (r) => r.json('email') !== undefined,
    });
    failRate.add(userRes.status !== 200);
    sleep(0.5);
  });

  // ── Group: History ─────────────────────────────────
  group('History', () => {
    const historyRes = http.get(`${BASE_URL}/history?per_page=5`, { headers });
    historyTrend.add(historyRes.timings.duration);
    check(historyRes, {
      'history success': (r) => r.status === 200,
      'history has data structure': (r) => r.json('data') !== undefined,
    });
    failRate.add(historyRes.status !== 200);
    sleep(0.5);
  });

  // ── Group: GDPR Compliance (Item #6) ───────────────
  group('GDPR Compliance', () => {
    // Retention policy (public)
    const retentionRes = http.get(`${BASE_URL}/user/retention-policy`, { headers });
    check(retentionRes, {
      'retention policy success': (r) => r.status === 200,
    });
    failRate.add(retentionRes.status !== 200);

    // Data export
    const exportRes = http.get(`${BASE_URL}/user/data/export`, { headers });
    exportTrend.add(exportRes.timings.duration);
    check(exportRes, {
      'data export success': (r) => r.status === 200,
      'export has user data': (r) => r.json('user') !== undefined,
    });
    failRate.add(exportRes.status !== 200);

    // Consent update
    const consentRes = http.post(`${BASE_URL}/user/consent`, {
      data_processing: true,
      marketing_emails: false,
      data_retention_months: 24,
      consent_version: '1.0.0',
    }, { headers });
    check(consentRes, {
      'consent update success': (r) => r.status === 200,
    });
    failRate.add(consentRes.status !== 200);

    sleep(0.5);
  });

  // ── Group: Health Check ────────────────────────────
  group('Health', () => {
    const healthRes = http.get(`${BASE_URL}/health`);
    healthTrend.add(healthRes.timings.duration);
    check(healthRes, {
      'health success': (r) => r.status === 200,
      'health has status': (r) => r.json('status') !== undefined,
    });
    failRate.add(healthRes.status !== 200);
  });

  sleep(1);
}

// ─── Teardown ────────────────────────────────────────

export function teardown(data) {
  if (data.token) {
    // Coba logout (ignore failure)
    const res = http.post(`${BASE_URL}/logout`, {}, {
      headers: { 'Authorization': `Bearer ${data.token}` },
    });
    console.log(`Teardown: logout status ${res.status}`);
  }
}
