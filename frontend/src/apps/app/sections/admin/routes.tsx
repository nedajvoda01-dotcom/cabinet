import React from "react";
import { UsersPage } from "../../../features/users";
import { Guard } from "../../../shared/rbac/Guard";
import { useAuth } from "../../../features/auth/ui/AuthProvider";

export const adminRoutes = [
  {
    path: "/admin/users",
    element: () => {
      const { user } = useAuth();
      return <Guard role={user?.role} permission="admin.users.view" fallback={<div className="p-4">Forbidden</div>}><UsersPage /></Guard>;
    },
  },
];
