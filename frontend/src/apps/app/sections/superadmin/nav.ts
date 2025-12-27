import type { NavItem } from "../../../shared/types/ui";

export const superadminNav: NavItem[] = [
  { label: "Access requests", to: "/superadmin", permission: "superadmin.accessRequests.view" },
];
