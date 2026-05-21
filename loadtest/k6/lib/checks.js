import { check } from 'k6';

export function parseJson(response) {
  try {
    return response.json();
  } catch (error) {
    return null;
  }
}

export function checkBulkAccepted(response) {
  const body = parseJson(response);

  return check(response, {
    'bulk returns 202': (r) => r.status === 202,
    'bulk has batch_id': () => Boolean(body && body.data && body.data.batch_id),
    'bulk accepted count exists': () => body && body.data && Number.isInteger(body.data.accepted_count),
  });
}

export function checkOk(response, label = 'request') {
  return check(response, {
    [`${label} returns 200`]: (r) => r.status === 200,
  });
}

export function summary(name) {
  return function handleSummary(data) {
    const metricValues = function metricValues(metricName) {
      return data.metrics && data.metrics[metricName] ? data.metrics[metricName].values : null;
    };

    const result = {};
    result.stdout = JSON.stringify({
      scenario: name,
      metrics: {
        http_reqs: metricValues('http_reqs'),
        http_req_failed: metricValues('http_req_failed'),
        http_req_duration: metricValues('http_req_duration'),
        checks: metricValues('checks'),
      },
    }, null, 2);
    result[`/scripts/results/${name}-summary.json`] = JSON.stringify(data, null, 2);

    return result;
  };
}
