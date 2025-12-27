import React from "react";
import type { AccessRequest } from "../model";

export function AccessRequestCard({ request, children }: { request: AccessRequest; children?: React.ReactNode }) {
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-3 space-y-2">
      <div className="text-sm font-medium">{request.email}</div>
      <div className="text-xs text-slate-500">Status: {request.status}</div>
      {children}
    </div>
  );
}
