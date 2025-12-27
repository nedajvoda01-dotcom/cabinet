export type Permission =
  | "pipeline.view"
  | "team.view"
  | "team.invite"
  | "analytics.view"
  | "admin.users.view"
  | "superadmin.accessRequests.view"
  | "superadmin.accessRequests.approve";

export const PERMISSIONS: Permission[] = [
  "pipeline.view",
  "team.view",
  "team.invite",
  "analytics.view",
  "admin.users.view",
  "superadmin.accessRequests.view",
  "superadmin.accessRequests.approve",
];
