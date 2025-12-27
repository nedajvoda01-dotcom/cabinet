import type { NavItem } from "../../../shared/types/ui";

export const adminNav: NavItem[] = [
  { label: "Users", to: "/admin/users", permission: "admin.users.view" },
];
