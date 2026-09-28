import type { ReactNode } from "react";

import { Link, NavLink, useNavigate } from "react-router-dom";

import { useAuth } from "../auth/AuthContext";

type Props = {
  children: ReactNode;
};

export function AppLayout({ children }: Props) {
  const { user, logout } = useAuth();

  const navigate = useNavigate();

  const roles = user?.roles ?? [];

  const isAdministrator = roles.includes("Administrator");

  const handleLogout = async () => {
    try {
      await logout();

      navigate("/login", {
        replace: true,
      });
    } catch (error) {
      console.error("Logout failed:", error);
    }
  };

  return (
    <div className="app-shell">
      <header className="topbar">
        <div className="topbar-inner">
          {/* Brand */}
          <Link to="/dashboard" className="brand">
            <div className="brand-logo">CI</div>

            <div className="brand-text">
              <strong>CricIntel</strong>

              <span>AI</span>
            </div>
          </Link>

          {/* Main navigation */}
          <nav className="main-navigation">
            <NavLink
              to="/dashboard"
              className={({ isActive }) =>
                isActive ? "nav-link active" : "nav-link"
              }
            >
              Dashboard
            </NavLink>

            <NavLink
              to="/organizations"
              className={({ isActive }) =>
                isActive ? "nav-link active" : "nav-link"
              }
            >
              Organizations
            </NavLink>

            {isAdministrator && (
              <NavLink
                to="/admin"
                className={({ isActive }) =>
                  isActive ? "nav-link active" : "nav-link"
                }
              >
                Administration
              </NavLink>
            )}
          </nav>

          {/* Right side */}
          <div className="topbar-user">
            <div className="user-avatar">
              {user?.name?.charAt(0)?.toUpperCase() ?? "U"}
            </div>

            <div className="user-info">
              <strong>{user?.name ?? "CricIntel User"}</strong>

              <span>{roles.length ? roles.join(", ") : "User"}</span>
            </div>

            <button
              type="button"
              className="logout-button"
              onClick={handleLogout}
            >
              Logout
            </button>
          </div>
        </div>
      </header>

      <main className="main-content">{children}</main>
    </div>
  );
}
