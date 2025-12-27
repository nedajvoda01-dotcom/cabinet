import type { User } from "../../features/auth/model";

let cachedUser: User | null = null;

export function setUser(user: User | null) {
  cachedUser = user;
}

export function getUser() {
  return cachedUser;
}
