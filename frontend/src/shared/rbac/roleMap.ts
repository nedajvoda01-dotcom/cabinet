import type { Role } from "./roles";
import type { Permission } from "./permissions";

export const ROLE_PERMISSIONS: Record<Role, Permission[]> = {
  superadmin: [
    "pipeline.view",
    "team.view",
    "team.invite",
    "analytics.view",
    "admin.users.view",
    "superadmin.accessRequests.view",
    "superadmin.accessRequests.approve",
  ],
  admin: [
    "pipeline.view",
    "team.view",
    "team.invite",
    "analytics.view",
    "admin.users.view",
  ],
  member: ["pipeline.view", "team.view"],
  guest: [],
};
