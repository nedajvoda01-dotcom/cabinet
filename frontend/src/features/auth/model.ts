import type { User, Role, LoginRequest, LoginResponse } from "./schemas";
import {
  getAccessToken as loadAccess,
  getRefreshToken as loadRefresh,
  setAccessToken as saveAccess,
  setRefreshToken as saveRefresh,
} from "../../shared/session/tokens";

export type { User, Role, LoginRequest, LoginResponse };

export const ACCESS_TOKEN_KEY = "autocontent.access_token";
export const REFRESH_TOKEN_KEY = "autocontent.refresh_token";

export function getAccessToken(): string | null {
  return loadAccess();
}
export function setAccessToken(token: string | null) {
  saveAccess(token);
}
export function getRefreshToken(): string | null {
  return loadRefresh();
}
export function setRefreshToken(token: string | null) {
  saveRefresh(token);
}

export function isAdmin(user: User | null | undefined) {
  return (user?.role ?? "guest") === "admin" || (user?.role ?? "guest") === "superadmin";
}

export function isMember(user: User | null | undefined) {
  return (user?.role ?? "guest") === "member";
}
