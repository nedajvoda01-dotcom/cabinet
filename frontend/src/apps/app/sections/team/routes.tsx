import React from "react";
import { TeamPage } from "../../../features/team";
import { Guard } from "../../../shared/rbac/Guard";
import { useAuth } from "../../../features/auth/ui/AuthProvider";

export const teamRoutes = [
  {
    path: "/team",
    element: () => {
      const { user } = useAuth();
      return <Guard role={user?.role} permission="team.view" fallback={<div className="p-4">Forbidden</div>}><TeamPage /></Guard>;
    },
  },
];
