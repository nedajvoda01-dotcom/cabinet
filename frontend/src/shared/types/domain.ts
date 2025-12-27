import type { Role } from "../rbac/roles";

export type User = {
  id: string;
  email: string;
  role: Role;
};
