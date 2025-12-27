import type { NavItem } from "../../shared/types/ui";
import { workNav } from "./sections/work/nav";
import { teamNav } from "./sections/team/nav";
import { adminNav } from "./sections/admin/nav";
import { superadminNav } from "./sections/superadmin/nav";
import { can } from "../../shared/rbac/can";
import type { Role } from "../../shared/rbac/roles";

export function navForRole(role: Role | undefined): NavItem[] {
  const all = [...workNav, ...teamNav, ...adminNav, ...superadminNav];
  return all.filter((item) => !item.permission || can(role, item.permission as any));
}
