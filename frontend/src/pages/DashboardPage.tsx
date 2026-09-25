import { useAuth } from '../auth/AuthContext';
import { AppLayout } from '../components/AppLayout';

export function DashboardPage() {
  const { user } = useAuth();

  return (
    <AppLayout>
      <h1>Dashboard</h1>

      <p>
        Welcome to CricIntel AI,{' '}
        <strong>{user?.name}</strong>.
      </p>

      <h2>Your Roles</h2>

      <ul>
        {user?.roles.map((role) => (
          <li key={role}>
            {role}
          </li>
        ))}
      </ul>

      <p>
        CricIntel foundation is operational.
      </p>
    </AppLayout>
  );
}
