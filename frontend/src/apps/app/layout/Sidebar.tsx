import React from "react";
import { Link, useLocation } from "react-router-dom";
import { navForRole } from "../nav";
import { useAuth } from "../../features/auth/ui/AuthProvider";

export function Sidebar() {
  const { user } = useAuth();
  const nav = navForRole(user?.role);
  const loc = useLocation();

  return (
    <aside className="w-56 border-r border-slate-200 bg-white min-h-screen p-3 space-y-2">
      {nav.map((item) => {
        const active = loc.pathname === item.to;
        return (
          <Link
            key={item.to}
            to={item.to}
            className={`block px-3 py-2 rounded-lg text-sm ${active ? "bg-slate-100 font-semibold" : "hover:bg-slate-50"}`}
          >
            {item.label}
          </Link>
        );
      })}
    </aside>
  );
}
