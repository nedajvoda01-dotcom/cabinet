import React from "react";

export function StatusPill({ status }: { status?: string }) {
  return <span className="text-xs px-2 py-1 rounded-full bg-slate-100">{status ?? "unknown"}</span>;
}
