// cabinet/frontend/src/features/auth/api.ts
import { http } from "../../shared/api/http";
import { ApiError } from "../../shared/api/errors";
import {
  LoginRequestSchema,
  LoginResponseSchema,
  RefreshResponseSchema,
  UserSchema,
} from "./schemas";
import {
  getAccessToken,
  getRefreshToken,
  setAccessToken,
  setRefreshToken,
} from "./model";

export class AuthApi {
  async login(input: unknown) {
    const payload = LoginRequestSchema.parse(input);
    const res = await http.post("/auth/login", payload, LoginResponseSchema);

    setAccessToken(res.access_token);
    if (res.refresh_token) setRefreshToken(res.refresh_token);

    return res;
  }

  async me() {
    return http.get("/auth/me", UserSchema);
  }

  async refresh() {
    const refresh_token = getRefreshToken();
    if (!refresh_token) throw new ApiError("No refresh token");

    const res = await http.post(
      "/auth/refresh",
      { refresh_token },
      RefreshResponseSchema
    );

    setAccessToken(res.access_token);
    return res;
  }

  async logout() {
    try {
      await http.post("/auth/logout", {});
    } finally {
      setAccessToken(null);
      setRefreshToken(null);
    }
  }

  // helper
  isLoggedIn() {
    return !!getAccessToken();
  }
}

export const authApi = new AuthApi();
