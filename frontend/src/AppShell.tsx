// cabinet/frontend/src/AppShell.tsx
import React, { useMemo } from "react";
import { BrowserRouter, Routes, Route, Navigate, Link, useLocation } from "react-router-dom";

import OperatorApp from "./apps/operator";
import AdminApp from "./apps/admin";
import { AuthProvider, RequireAuth, LoginPage, useAuth } from "./features/auth/ui";

function ShellLayout({ children }: { children: React.ReactNode }) {
  const { user, logout } = useAuth();
  const loc = useLocation();

  const isAdmin = user?.role === "admin";
  const base = loc.pathname.startsWith("/admin") ? "/admin" : "/operator";

  const navItems = useMemo(() => {
    const common = [
      { to: `${base}/cards`, label: "Cards" },
      { to: `${base}/parser`, label: "Parser" },
      { to: `${base}/photos`, label: "Photos" },
      { to: `${base}/export`, label: "Export" },
      { to: `${base}/publish`, label: "Publish" },
    ];
    return common;
  }, [base]);

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="sticky top-0 z-10 bg-white border-b border-gray-200">
        <div className="max-w-6xl mx-auto px-4 py-3 flex items-center gap-3">
          <Link to={base} className="font-bold text-lg">
            Autocontent
          </Link>

          <nav className="flex gap-2 text-sm">
            {navItems.map((n) => (
              <Link
                key={n.to}
                to={n.to}
                className="px-2 py-1 rounded-lg hover:bg-gray-100"
              >
                {n.label}
              </Link>
            ))}
          </nav>

          <div className="ml-auto flex items-center gap-2 text-sm">
            {user && (
              <span className="text-gray-600">
                {user.email} • {user.role ?? "unknown"}
              </span>
            )}
            {isAdmin && (
              <Link
                to={base === "/admin" ? "/operator" : "/admin"}
                className="px-2 py-1 rounded-lg border hover:bg-gray-50"
              >
                {base === "/admin" ? "Operator" : "Admin"}
              </Link>
            )}
            {user && (
              <button
                onClick={() => logout()}
                className="px-2 py-1 rounded-lg border hover:bg-gray-50"
              >
                Logout
              </button>
            )}
          </div>
        </div>
      </header>

      <main className="max-w-6xl mx-auto">{children}</main>
    </div>
  );
}

function ProtectedApps() {
  return (
    <RequireAuth>
      <ShellLayout>
        <Routes>
          <Route path="/" element={<RootRedirect />} />
          <Route path="/operator/*" element={<OperatorApp />} />
          <Route path="/admin/*" element={<AdminApp />} />
          <Route path="*" element={<div className="p-4 text-sm">Not found</div>} />
        </Routes>
      </ShellLayout>
    </RequireAuth>
  );
}

function RootRedirect() {
  const { user } = useAuth();
  const to = user?.role === "admin" ? "/admin" : "/operator";
  return <Navigate to={to} replace />;
}

export default function AppShell() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/*" element={<ProtectedApps />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}
