import type { Permission } from "./permissions";
import { ROLE_PERMISSIONS } from "./roleMap";
import type { Role } from "./roles";

export function can(role: Role | undefined, permission: Permission): boolean {
  if (!role) return false;
  return ROLE_PERMISSIONS[role]?.includes(permission) ?? false;
}
