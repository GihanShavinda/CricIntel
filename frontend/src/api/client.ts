import axios from 'axios';

const API_URL =
  import.meta.env.VITE_API_URL ??
  'http://localhost:8000';

export const api = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

export const initializeCsrf = async () => {
  await api.get('/sanctum/csrf-cookie');
};
