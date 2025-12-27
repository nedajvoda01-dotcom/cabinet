import React from "react";
import { Button } from "../../shared/ui/Button";
import type { AccessRequest } from "../model";
import { accessRequestsApi } from "../api";

export function ApproveRejectBar({ request }: { request: AccessRequest }) {
  const onApprove = () => accessRequestsApi.approve(request.id);
  const onReject = () => accessRequestsApi.reject(request.id);
  return (
    <div className="flex gap-2">
      <Button onClick={onApprove}>Approve</Button>
      <Button onClick={onReject} variant="ghost">
        Reject
      </Button>
    </div>
  );
}
