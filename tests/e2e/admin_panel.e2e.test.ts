// cabinet/tests/e2e/admin_panel.e2e.test.ts

import { describe, it, expect } from "@jest/globals";

const API = process.env.E2E_API_URL || "http://localhost:8080/api";

async function api(path: string, init?: RequestInit) {
  const res = await fetch(`${API}${path}`, {
    headers: { "Content-Type": "application/json" },
    ...init,
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(`API ${path} failed: ${res.status} ${JSON.stringify(json)}`);
  return json;
}

describe("E2E admin smoke", () => {
  it("health/stats/logs endpoints work", async () => {
    const health = await api("/admin/health");
    expect(health.status).toBe("ok");

    const stats = await api("/admin/stats");
    expect(stats).toHaveProperty("queues");

    const logs = await api("/admin/logs");
    expect(Array.isArray(logs.items)).toBeTruthy();
  });
});
