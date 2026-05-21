import {
  MAX_RECIPIENT_ID,
  RECIPIENTS_PER_BATCH,
  START_RECIPIENT_ID,
} from './config.js';

export function recipientIds(size = RECIPIENTS_PER_BATCH, seed = 0) {
  const maxAvailable = Math.max(1, MAX_RECIPIENT_ID - START_RECIPIENT_ID + 1);
  const finalSize = Math.min(size, maxAvailable);
  const ids = [];

  for (let i = 0; i < finalSize; i += 1) {
    ids.push(START_RECIPIENT_ID + ((seed + i) % maxAvailable));
  }

  return ids;
}

export function uniqueKey(prefix) {
  return `${prefix}-${Date.now()}-${__VU}-${__ITER}-${Math.random().toString(16).slice(2)}`;
}

export function bulkPayload({
  channel = 'email',
  type = 'transactional',
  message = 'Load test message',
  keyPrefix = 'loadtest',
  recipients = RECIPIENTS_PER_BATCH,
  fixedKey = null,
  seed = __ITER + __VU,
} = {}) {
  return {
    channel,
    type,
    message,
    recipient_ids: recipientIds(recipients, seed),
    idempotency_key: fixedKey || uniqueKey(keyPrefix),
    metadata: {
      source_service: 'k6',
      scenario: keyPrefix,
      vu: String(__VU),
      iter: String(__ITER),
    },
  };
}
