import React, { useState, useEffect } from "react";
import { useAuth } from "./AuthProvider";
import { Input } from "../../shared/ui/Input";
import { Button } from "../../shared/ui/Button";

export function LoginForm() {
  const { login, loading, error, user } = useAuth();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  useEffect(() => {
    if (user && typeof window !== "undefined") {
      window.location.replace("/");
    }
  }, [user]);

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    await login(email, password);
  };

  return (
    <form onSubmit={onSubmit} className="space-y-3 bg-white p-6 rounded-xl border border-slate-200 shadow">
      <div>
        <label className="text-sm">Email</label>
        <Input
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
          autoComplete="username"
          className="w-full mt-1"
        />
      </div>
      <div>
        <label className="text-sm">Password</label>
        <Input
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
          autoComplete="current-password"
          className="w-full mt-1"
        />
      </div>
      {error && <div className="text-sm text-red-600">{error}</div>}
      <Button type="submit" disabled={loading} className="w-full">
        {loading ? "Signing in…" : "Sign in"}
      </Button>
    </form>
  );
}
