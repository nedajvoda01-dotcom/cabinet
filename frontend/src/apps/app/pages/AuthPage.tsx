import React from "react";
import { LoginForm, RequestAccessForm, RequestStatus, useAuth } from "../../features/auth";

export function AuthPage() {
  const { user } = useAuth();
  if (user) return <RequestStatus />;
  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 p-4">
      <div className="w-full max-w-md space-y-4">
        <LoginForm />
        <RequestAccessForm />
      </div>
    </div>
  );
}
