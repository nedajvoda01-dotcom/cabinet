import React from "react";
import type { Permission } from "./permissions";
import { can } from "./can";
import type { Role } from "./roles";

interface GuardProps {
  role: Role | undefined;
  permission: Permission;
  fallback?: React.ReactNode;
  children: React.ReactNode;
}

export function Guard({ role, permission, fallback = null, children }: GuardProps) {
  if (!can(role, permission)) return <>{fallback}</>;
  return <>{children}</>;
}
