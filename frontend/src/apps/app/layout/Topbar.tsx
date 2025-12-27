import React from "react";
import { useAuth } from "../../features/auth/ui/AuthProvider";
import { LogoutButton } from "../../features/auth";

export function Topbar() {
  const { user } = useAuth();
  return (
    <header className="h-14 border-b border-slate-200 bg-white flex items-center px-4 justify-between">
      <div className="font-semibold">Autocontent</div>
      <div className="flex items-center gap-2 text-sm">
        {user && <span className="text-slate-600">{user.email} • {user.role ?? "guest"}</span>}
        <LogoutButton />
      </div>
    </header>
  );
}
