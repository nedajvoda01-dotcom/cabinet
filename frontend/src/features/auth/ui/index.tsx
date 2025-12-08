// cabinet/frontend/src/features/auth/ui/index.tsx
import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";
import { authApi } from "../api";
import type { User } from "../schemas";
import { getAccessToken, setAccessToken } from "../model";
import { normalizeError } from "../../../shared/api/errors";

// ----------------- Context -----------------
type AuthState = {
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
    // если токена нет — просто не грузим me
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

// ----------------- Guard -----------------
/**
 * Обёртка для защищённых страниц.
 * Если нет токена или user — редиректит на /login.
 */
export function RequireAuth({
  children,
  fallback = null,
}: {
  children: React.ReactNode;
  fallback?: React.ReactNode;
}) {
  const { user, loading } = useAuth();

  if (loading) return <div className="p-4">Checking auth…</div>;

  if (!getAccessToken() || !user) {
    if (typeof window !== "undefined") {
      window.location.href = "/login";
    }
    return fallback;
  }

  return <>{children}</>;
}

// ----------------- Login Page -----------------
export function LoginPage() {
  const { login, loading, error, user } = useAuth();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  useEffect(() => {
    if (user && typeof window !== "undefined") {
      window.location.href = user.role === "admin" ? "/admin" : "/operator";
    }
  }, [user]);

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    await login(email, password);
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 p-4">
      <form
        onSubmit={onSubmit}
        className="w-full max-w-sm bg-white border border-gray-200 shadow rounded-2xl p-6 space-y-3"
      >
        <h1 className="text-xl font-bold">Autocontent Login</h1>

        <label className="block text-sm">
          Email
          <input
            className="mt-1 w-full border rounded-xl px-3 py-2 text-sm"
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            autoComplete="username"
          />
        </label>

        <label className="block text-sm">
          Password
          <input
            className="mt-1 w-full border rounded-xl px-3 py-2 text-sm"
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            autoComplete="current-password"
          />
        </label>

        {error && (
          <div className="text-sm text-red-600 bg-red-50 border border-red-200 rounded-xl p-2">
            {error}
          </div>
        )}

        <button
          disabled={loading}
          className="w-full py-2 rounded-xl border text-sm font-medium hover:bg-gray-50 disabled:opacity-50"
        >
          {loading ? "Signing in…" : "Sign in"}
        </button>
      </form>
    </div>
  );
}
