/**
 * CSRF-aware API wrapper for Drupal JSON endpoints.
 */

let cachedCsrfToken = null;

/**
 * Fetches a CSRF token from the session.
 *
 * @returns {Promise<string>}
 *   The CSRF token.
 */
async function getCsrfToken() {
  if (cachedCsrfToken) {
    return cachedCsrfToken;
  }

  const response = await fetch('/session/token');
  cachedCsrfToken = await response.text();
  return cachedCsrfToken;
}

/**
 * Makes a GET request to a Kanban API endpoint.
 *
 * @param {string} url
 *   The API endpoint URL.
 *
 * @returns {Promise<Object>}
 *   The parsed JSON response.
 */
export async function fetchKanban(url) {
  const response = await fetch(url, {
    headers: {
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error(`API error: ${response.status}`);
  }

  return response.json();
}

/**
 * Makes a PATCH request with CSRF protection.
 *
 * @param {string} url
 *   The API endpoint URL.
 * @param {Object} data
 *   The data to send.
 *
 * @returns {Promise<Object>}
 *   The parsed JSON response.
 */
export async function patchKanban(url, data) {
  const token = await getCsrfToken();

  const response = await fetch(url, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token,
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.error || `API error: ${response.status}`);
  }

  return response.json();
}

/**
 * Makes a POST request with CSRF protection.
 *
 * @param {string} url
 *   The API endpoint URL.
 * @param {Object} data
 *   The data to send.
 *
 * @returns {Promise<Object>}
 *   The parsed JSON response.
 */
export async function postKanban(url, data) {
  const token = await getCsrfToken();

  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token,
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.error || `API error: ${response.status}`);
  }

  return response.json();
}

/**
 * Makes a DELETE request with CSRF protection.
 *
 * @param {string} url
 *   The API endpoint URL.
 *
 * @returns {Promise<void>}
 *   Resolves on success (204 No Content).
 */
export async function deleteKanban(url) {
  const token = await getCsrfToken();

  const response = await fetch(url, {
    method: 'DELETE',
    headers: {
      'X-CSRF-Token': token,
    },
  });

  if (!response.ok) {
    let errorMsg = `API error: ${response.status}`;
    try {
      const error = await response.json();
      errorMsg = error.error || errorMsg;
    } catch (e) {
      // Response is not JSON, use status code message.
    }
    throw new Error(errorMsg);
  }
}
