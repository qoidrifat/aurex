/**
 * K6 Load Test Script — AUREX API
 *
 * Simulasi concurrent user untuk mengukur performa endpoint utama.
 *
 * Usage:
 *   k6 run tests/k6/load-test.js
 *   k6 run --vus 50 --duration 60s tests/k6/load-test.js
 *
 * Options:
 *   --vus       Virtual users (default: 10)
 *   --duration  Test duration (default: 30s)
 */

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// ─── Custom Metrics ──────────────────────────────────

const failRate = new Rate('failed_requests');
const loginTrend = new Trend('login_duration');
const analyzeTrend = new Trend('analyze_duration');
const historyTrend = new Trend('history_duration');

// ─── Configuration ───────────────────────────────────

const BASE_URL = __ENV.API_URL || 'http://localhost:8000/api/v1';
const DEFAULT_EMAIL = 'loadtest@aurex.app';
const DEFAULT_PASSWORD = 'LoadTest@123';

export const options = {
  stages: [
    { duration: '10s', target: 10 },  // Ramp up ke 10 users
    { duration: '20s', target: 10 },  // Maintain 10 users
    { duration: '10s', target: 0 },   // Ramp down
  ],
  thresholds: {
    failed_requests: ['rate<0.05'],        // Maks 5% failure
    http_req_duration: ['p(95)<3000'],     // 95% request < 3 detik
    login_duration: ['p(95)<2000'],        // Login < 2 detik
    analyze_duration: ['p(95)<5000'],      // Analyze < 5 detik
  },
};

// ─── Setup — Registrasi user test ────────────────────

export function setup() {
  // Coba register user test (ignore jika sudah ada)
  const res = http.post(`${BASE_URL}/register`, {
    name: 'Load Test User',
    email: DEFAULT_EMAIL,
    password: DEFAULT_PASSWORD,
    password_confirmation: DEFAULT_PASSWORD,
  });
  return { token: res.json('access_token') };
}

// ─── Main Test ───────────────────────────────────────

export default function (data) {
  const token = data.token;
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`,
  };

  group('Authentication', () => {
    // Login
    const loginRes = http.post(`${BASE_URL}/login`, {
      email: DEFAULT_EMAIL,
      password: DEFAULT_PASSWORD,
    }, { headers: { 'Content-Type': 'application/json' } });

    loginTrend.add(loginRes.timings.duration);
    check(loginRes, {
      'login success': (r) => r.status === 200,
      'login has token': (r) => r.json('access_token') !== undefined,
    });
    failRate.add(loginRes.status !== 200);

    // User info
    const userRes = http.get(`${BASE_URL}/user`, { headers });
    check(userRes, { 'user info success': (r) => r.status === 200 });
  });

  sleep(1);

  group('Analysis', () => {
    // History
    const historyRes = http.get(`${BASE_URL}/history`, { headers });
    historyTrend.add(historyRes.timings.duration);
    check(historyRes, {
      'history success': (r) => r.status === 200,
    });
    failRate.add(historyRes.status !== 200);
  });

  sleep(1);

  group('Health', () => {
    const healthRes = http.get(`${BASE_URL}/health`);
    check(healthRes, { 'health success': (r) => r.status === 200 });
    failRate.add(healthRes.status !== 200);
  });
}

// ─── Teardown ────────────────────────────────────────

export function teardown(data) {
  if (data.token) {
    http.post(`${BASE_URL}/logout`, {}, {
      headers: { 'Authorization': `Bearer ${data.token}` },
    });
  }
}
