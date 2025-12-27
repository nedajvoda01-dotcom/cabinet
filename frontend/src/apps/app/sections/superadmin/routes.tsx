import React from "react";
import { AccessRequestsPage } from "../../../features/accessRequests";
import { Guard } from "../../../shared/rbac/Guard";
import { useAuth } from "../../../features/auth/ui/AuthProvider";

export const superadminRoutes = [
  {
    path: "/superadmin",
    element: () => {
      const { user } = useAuth();
      return (
        <Guard role={user?.role} permission="superadmin.accessRequests.view" fallback={<div className="p-4">Forbidden</div>}>
          <AccessRequestsPage />
        </Guard>
      );
    },
  },
];
