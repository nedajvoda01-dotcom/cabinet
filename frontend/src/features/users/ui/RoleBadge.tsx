import React from "react";

export function RoleBadge({ role }: { role: string }) {
  return <span className="inline-flex px-2 py-1 rounded-md bg-slate-100 text-xs">{role}</span>;
}
