import React from "react";
import { Routes, Route, Navigate } from "react-router-dom";
import { RequireAuth, useAuth } from "../../features/auth/ui/AuthProvider";
import { AppShell } from "./layout/AppShell";
import { routes } from "./routes";
import { AuthPage } from "./pages/AuthPage";
import { NotFoundPage } from "./pages/NotFoundPage";

export default function AppRoutes() {
  return (
    <Routes>
      <Route path="/login" element={<AuthPage />} />
      <Route
        path="/*"
        element={
          <RequireAuth>
            <AppShell />
          </RequireAuth>
        }
      />
      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}

export function AppContentRoutes() {
  const { user } = useAuth();
  return (
    <Routes>
      {routes.map((r) => (
        <Route key={r.path} path={r.path} element={<r.element />} />
      ))}
      <Route path="/" element={<Navigate to={user?.role === "superadmin" ? "/superadmin" : "/work"} replace />} />
      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}
