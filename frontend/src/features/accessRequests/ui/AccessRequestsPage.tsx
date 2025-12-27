import React from "react";
import { accessRequestsApi } from "../api";
import type { AccessRequest } from "../model";
import { ApproveRejectBar } from "./ApproveRejectBar";
import { AccessRequestCard } from "./AccessRequestCard";

export function AccessRequestsPage() {
  const [items, setItems] = React.useState<AccessRequest[]>([]);

  React.useEffect(() => {
    accessRequestsApi.list().then(setItems);
  }, []);

  return (
    <div className="p-4 space-y-3">
      <h1 className="text-xl font-semibold">Access requests</h1>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
        {items.map((req) => (
          <AccessRequestCard key={req.id} request={req}>
            <ApproveRejectBar request={req} />
          </AccessRequestCard>
        ))}
        {items.length === 0 && <div className="text-sm text-slate-500">No pending requests</div>}
      </div>
    </div>
  );
}
