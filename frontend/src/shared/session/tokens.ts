import { load, save } from "./storage";

export const ACCESS_TOKEN_KEY = "autocontent.access_token";
export const REFRESH_TOKEN_KEY = "autocontent.refresh_token";

export function getAccessToken() {
  return load(ACCESS_TOKEN_KEY);
}
export function setAccessToken(token: string | null) {
  save(ACCESS_TOKEN_KEY, token);
}
export function getRefreshToken() {
  return load(REFRESH_TOKEN_KEY);
}
export function setRefreshToken(token: string | null) {
  save(REFRESH_TOKEN_KEY, token);
}
