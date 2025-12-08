// cabinet/tests/e2e/dlq_retry.e2e.test.ts

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

async function waitUntil(fn: () => Promise<boolean>, timeoutMs = 30000, stepMs = 500) {
  const started = Date.now();
  while (Date.now() - started < timeoutMs) {
    if (await fn()) return;
    await new Promise((r) => setTimeout(r, stepMs));
  }
  throw new Error("waitUntil timeout");
}

describe("E2E DLQ + retry", () => {
  it("moves failing job to DLQ and retries it", async () => {
    const card = await api("/cards", {
      method: "POST",
      body: JSON.stringify({ title: "draft", source: "auto_ru" }),
    });
    const cardId = card.id as number;

    // триггер parse с опцией "force_fail" — в e2e окружении
    // это должен понимать FakeParserAdapter (или env)
    await api(`/cards/${cardId}/parse`, {
      method: "POST",
      body: JSON.stringify({
        url: "https://auto.ru/bad/url",
        correlation_id: `cid-fail-${cardId}`,
        options: { force_fail: true },
      }),
    });

    // ждём parser_failed
    await waitUntil(async () => {
      const c = await api(`/cards/${cardId}`);
      return c.status === "parser_failed";
    }, 60000);

    // в DLQ должна быть запись
    const dlq = await api("/admin/dlq");
    expect(Array.isArray(dlq.items)).toBeTruthy();
    const dlqItem = dlq.items.find((x: any) => x.entity_id === cardId);
    expect(dlqItem).toBeTruthy();

    // retry DLQ item
    await api(`/admin/dlq/${dlqItem.id}/retry`, { method: "POST" });

    // теперь триггер нормальный parse
    await api(`/cards/${cardId}/parse`, {
      method: "POST",
      body: JSON.stringify({
        url: "https://auto.ru/cars/used/sale/audi/a6/1122334455.html",
        correlation_id: `cid-ok-${cardId}`,
      }),
    });

    // ждём parser_done
    await waitUntil(async () => {
      const c = await api(`/cards/${cardId}`);
      return c.status === "parser_done";
    }, 60000);

    const cDone = await api(`/cards/${cardId}`);
    expect(cDone.status).toBe("parser_done");
  });
});
