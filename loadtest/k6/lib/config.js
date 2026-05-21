export const BASE_URL = __ENV.BASE_URL || 'http://localhost:18080';
export const VUS = Number(__ENV.VUS || 10);
export const DURATION = __ENV.DURATION || '1m';
export const ITERATIONS = Number(__ENV.ITERATIONS || 10);
export const RECIPIENTS_PER_BATCH = Number(__ENV.RECIPIENTS_PER_BATCH || 3);
export const START_RECIPIENT_ID = Number(__ENV.START_RECIPIENT_ID || 11);
export const MAX_RECIPIENT_ID = Number(__ENV.MAX_RECIPIENT_ID || 110);

export const DEFAULT_HEADERS = {
  'Content-Type': 'application/json',
  Accept: 'application/json',
};

export const DEFAULT_THRESHOLDS = {
  http_req_failed: ['rate<0.01'],
  http_req_duration: ['p(95)<750', 'p(99)<2000'],
  checks: ['rate>0.99'],
};
