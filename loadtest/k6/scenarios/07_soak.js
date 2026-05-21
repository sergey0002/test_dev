import http from 'k6/http';
import { sleep } from 'k6';
import { BASE_URL, DEFAULT_HEADERS, DEFAULT_THRESHOLDS } from '../lib/config.js';
import { bulkPayload } from '../lib/payloads.js';
import { checkBulkAccepted, summary } from '../lib/checks.js';

export const options = {
  vus: Number(__ENV.VUS || 10),
  duration: __ENV.DURATION || '5m',
  thresholds: DEFAULT_THRESHOLDS,
};

export default function () {
  const transactional = (__ITER + __VU) % 5 === 0;
  const payload = bulkPayload({
    type: transactional ? 'transactional' : 'marketing',
    keyPrefix: transactional ? 'soak-transactional' : 'soak-marketing',
    recipients: Number(__ENV.RECIPIENTS_PER_BATCH || 5),
    message: transactional ? 'Soak transactional message' : 'Soak marketing message',
  });

  checkBulkAccepted(http.post(`${BASE_URL}/api/v1/notifications/bulk`, JSON.stringify(payload), {
    headers: DEFAULT_HEADERS,
  }));

  sleep(0.2);
}

export const handleSummary = summary('07_soak');
