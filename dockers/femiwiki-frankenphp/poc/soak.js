import http from 'k6/http';
import { check } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const BASE = __ENV.BASE || 'http://localhost:8080';
const SOAK_N = __ENV.SOAK_N || '5000000'; // tune so Module:Soak burns ~1-1.5s CPU
const soakLat = new Trend('soak_latency', true);
const bombLat = new Trend('bomb_latency', true);
const soakBadTimeout = new Counter('soak_spurious_timeout'); // cross-thread timer bleed
const bombNoTimeout = new Counter('bomb_missing_timeout'); // per-thread timer failed to fire
const http5xx = new Counter('http_5xx'); // segfault/500 reaching clients
const backpressure = new Counter('backpressure_503'); // Caddy max_wait_time, NOT a crash

export const options = {
  scenarios: {
    soak: {
      executor: 'constant-vus',
      vus: 3,
      duration: __ENV.DUR || '30m',
      exec: 'soak',
    },
    bomb: {
      executor: 'constant-vus',
      vus: 1,
      duration: __ENV.DUR || '30m',
      exec: 'bomb',
    },
  },
  thresholds: {
    soak_spurious_timeout: ['count==0'],
    bomb_missing_timeout: ['count==0'],
    http_5xx: ['count==0'],
    soak_latency: ['p(99)<3000'], // tune to a recorded single-request baseline
  },
};
// uselang=en makes the timeout message a stable English string we can match.
function parse(t) {
  return http.get(
    `${BASE}/api.php?action=parse&format=json&uselang=en&contentmodel=wikitext&disablelimitreport=1&text=${encodeURIComponent(t)}`,
  );
}
function classify(r) {
  if (r.status === 503) {
    backpressure.add(1);
    return false;
  }
  if (r.status >= 500) {
    http5xx.add(1);
  }
  return true;
}
const MARK =
  /time allocated for running scripts|scribunto-common-timeout|exceeded the time/i;
export function soak() {
  const r = parse(`<!--${Math.random()}-->{{#invoke:Soak|run|${SOAK_N}}}`);
  soakLat.add(r.timings.duration);
  if (classify(r) && MARK.test(r.body)) soakBadTimeout.add(1);
  check(r, { 200: (x) => x.status === 200 || x.status === 503 });
}
export function bomb() {
  const r = parse(`<!--${Math.random()}-->{{#invoke:CpuBomb|run}}`);
  bombLat.add(r.timings.duration);
  if (classify(r) && !MARK.test(r.body)) bombNoTimeout.add(1);
}
