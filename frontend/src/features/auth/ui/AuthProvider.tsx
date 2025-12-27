import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";
import { authApi } from "../api";
import type { User } from "../model";
import { getAccessToken, setAccessToken } from "../model";
import { normalizeError } from "../../shared/api/errors";

export type AuthState = {
  user: User | null;
  loading: boolean;
  error: string | null;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshMe: () => Promise<void>;
};

const AuthContext = createContext<AuthState | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const refreshMe = useCallback(async () => {
    try {
      setLoading(true);
      const me = await authApi.me();
      setUser(me);
      setError(null);
    } catch (e) {
      setUser(null);
      setError(normalizeError(e).message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!getAccessToken()) {
      setLoading(false);
      return;
    }
    refreshMe();
  }, [refreshMe]);

  const login = useCallback(async (email: string, password: string) => {
    try {
      setLoading(true);
      const res = await authApi.login({ email, password });
      setUser(res.user);
      setError(null);
    } catch (e) {
      setError(normalizeError(e).message);
      setUser(null);
    } finally {
      setLoading(false);
    }
  }, []);

  const logout = useCallback(async () => {
    await authApi.logout();
    setUser(null);
    setAccessToken(null);
  }, []);

  const value = useMemo<AuthState>(
    () => ({ user, loading, error, login, logout, refreshMe }),
    [user, loading, error, login, logout, refreshMe]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used inside AuthProvider");
  return ctx;
}

export function RequireAuth({ children }: { children: React.ReactNode }) {
  const { user, loading } = useAuth();
  if (loading) return <div className="p-4 text-sm">Checking auth…</div>;
  if (!getAccessToken() || !user) return <div className="p-4 text-sm">Please login</div>;
  return <>{children}</>;
}
