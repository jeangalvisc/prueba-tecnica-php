/**
 * Cliente HTTP para consumir la API REST de reservas.
 */

export class ApiException extends Error {
  /**
   * @param {number} status
   * @param {string} message
   * @param {Record<string, string>} [fields]
   */
  constructor(status, message, fields = {}) {
    super(message);
    this.name = 'ApiException';
    this.status = status;
    this.fields = fields;
  }
}

export const api = {
  /**
   * @param {string} url
   * @param {Record<string, any>} [params]
   */
  async get(url, params = {}) {
    const query = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
      if (value !== null && value !== undefined && value !== '') {
        query.append(key, String(value));
      }
    }
    const fullUrl = query.toString() ? `${url}?${query.toString()}` : url;
    return this.request(fullUrl, { method: 'GET' });
  },

  /**
   * @param {string} url
   * @param {Record<string, any>} data
   */
  async post(url, data) {
    return this.request(url, {
      method: 'POST',
      body: JSON.stringify(data),
    });
  },

  /**
   * @param {string} url
   * @param {Record<string, any>} data
   */
  async patch(url, data) {
    return this.request(url, {
      method: 'PATCH',
      body: JSON.stringify(data),
    });
  },

  /**
   * @private
   * @param {string} url
   * @param {RequestInit} options
   */
  async request(url, options = {}) {
    const headers = {
      'Accept': 'application/json',
      ...(options.headers || {}),
    };

    if (options.body && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }

    try {
      const response = await fetch(url, { ...options, headers });
      const contentType = response.headers.get('content-type') || '';
      const isJson = contentType.includes('application/json');

      const data = isJson ? await response.json() : null;

      if (!response.ok) {
        const message = data?.error?.message || `Error del servidor (${response.status})`;
        const fields = data?.error?.fields || {};
        throw new ApiException(response.status, message, fields);
      }

      return data;
    } catch (err) {
      if (err instanceof ApiException) {
        throw err;
      }
      throw new ApiException(0, 'No se pudo conectar con el servidor. Comprueba tu conexión a la red.');
    }
  },
};
