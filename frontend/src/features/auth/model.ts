// cabinet/frontend/src/features/auth/model.ts
import type { User, Role, LoginRequest, LoginResponse } from "./schemas";

export type { User, Role, LoginRequest, LoginResponse };

export const ACCESS_TOKEN_KEY = "autocontent.access_token";
export const REFRESH_TOKEN_KEY = "autocontent.refresh_token";

export function getAccessToken(): string | null {
  try { return localStorage.getItem(ACCESS_TOKEN_KEY); } catch { return null; }
}
export function setAccessToken(token: string | null) {
  try {
    if (token) localStorage.setItem(ACCESS_TOKEN_KEY, token);
    else localStorage.removeItem(ACCESS_TOKEN_KEY);
  } catch {}
}

export function getRefreshToken(): string | null {
  try { return localStorage.getItem(REFRESH_TOKEN_KEY); } catch { return null; }
}
export function setRefreshToken(token: string | null) {
  try {
    if (token) localStorage.setItem(REFRESH_TOKEN_KEY, token);
    else localStorage.removeItem(REFRESH_TOKEN_KEY);
  } catch {}
}

export function isAdmin(user: User | null | undefined) {
  return (user?.role ?? "unknown") === "admin";
}

export function isOperator(user: User | null | undefined) {
  return (user?.role ?? "unknown") === "operator";
}
