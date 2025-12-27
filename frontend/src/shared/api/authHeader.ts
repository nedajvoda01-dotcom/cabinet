export function authHeader(token: string | null | undefined) {
  return token ? { Authorization: `Bearer ${token}` } : {};
}
