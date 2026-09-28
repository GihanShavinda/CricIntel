import axios from 'axios';

const API_URL =
  (import.meta as ImportMeta & {
    env?: {
      VITE_API_URL?: string;
    };
  }).env?.VITE_API_URL ??
  'http://localhost:8000';

export const api = axios.create({
  baseURL: API_URL,

  // Required for Laravel Sanctum SPA cookies.
  withCredentials: true,

  // Axios automatically reads XSRF-TOKEN and sends
  // X-XSRF-TOKEN when communicating with Laravel.
  withXSRFToken: true,

  headers: {
    Accept: 'application/json',
  },
});

/*
 * Important:
 *
 * Never globally force:
 *
 * Content-Type: application/json
 *
 * because organization/club logo uploads use FormData.
 *
 * When data is FormData, the browser must generate:
 *
 * multipart/form-data; boundary=...
 */
api.interceptors.request.use((config) => {
  if (config.data instanceof FormData) {
    /*
     * Remove any Content-Type value that may have been
     * inherited from Axios defaults.
     *
     * The browser will generate the correct multipart
     * Content-Type including its boundary.
     */
    if (config.headers) {
      delete config.headers['Content-Type'];
    }
  } else {
    /*
     * Normal JSON API requests.
     */
    if (
      config.data !== undefined &&
      config.data !== null &&
      config.headers
    ) {
      config.headers['Content-Type'] =
        'application/json';
    }
  }

  return config;
});

/*
 * Get Laravel Sanctum CSRF cookie before
 * register/login requests.
 */
export async function initializeCsrf(): Promise<void> {
  await api.get('/sanctum/csrf-cookie');
}