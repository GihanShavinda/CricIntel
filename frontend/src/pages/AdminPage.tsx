import {
  useEffect,
  useState,
} from 'react';

import { api } from '../api/client';
import { AppLayout } from '../components/AppLayout';

export function AdminPage() {
  const [status, setStatus] =
    useState('Checking authorization...');

  useEffect(() => {
    api
      .get('/api/v1/admin/ping')
      .then(() => {
        setStatus(
          'Administrator authorization confirmed.'
        );
      })
      .catch(() => {
        setStatus(
          'Administrator authorization denied.'
        );
      });
  }, []);

  return (
    <AppLayout>
      <h1>Administration</h1>
      <p>{status}</p>
    </AppLayout>
  );
}
