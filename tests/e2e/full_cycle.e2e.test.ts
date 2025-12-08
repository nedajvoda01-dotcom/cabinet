// cabinet/tests/e2e/full_cycle.e2e.test.ts

import { describe, it, expect } from "@jest/globals";

/**
 * E2E: Full cycle
 * draft -> parser -> photos -> export -> publish -> published
 *
 * Тут тест не лезет в UI, а гоняет backend через публичные API.
 * В e2e окружении ожидается:
 *  - поднят backend
 *  - external сервисы замоканы (fixtures/contracts)
 *  - workers запущены или есть test-runner для tick()
 */

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

describe("E2E full cycle", () => {
  it("creates card and publishes it", async () => {
    // 1) create card
    const card = await api("/cards", {
      method: "POST",
      body: JSON.stringify({
        title: "draft",
        source: "auto_ru",
      }),
    });
    expect(card.id).toBeTruthy();
    const cardId = card.id as number;

    // 2) trigger parse
    await api(`/cards/${cardId}/parse`, {
      method: "POST",
      body: JSON.stringify({
        url: "https://auto.ru/cars/used/sale/audi/a6/1122334455.html",
        correlation_id: `cid-${cardId}`,
      }),
    });

    // wait parser_done
    await waitUntil(async () => {
      const c = await api(`/cards/${cardId}`);
      return c.status === "parser_done";
    });

    // 3) trigger photos
    await api(`/cards/${cardId}/photos`, {
      method: "POST",
      body: JSON.stringify({ correlation_id: `cid-photos-${cardId}` }),
    });

    // wait photos_done
    await waitUntil(async () => {
      const c = await api(`/cards/${cardId}`);
      return c.status === "photos_done";
    });

    // 4) create export for this card
    const exp = await api("/export", {
      method: "POST",
      body: JSON.stringify({
        card_ids: [cardId],
        options: { format: "avito_xml" },
      }),
    });
    expect(exp.id).toBeTruthy();
    const exportId = exp.id as number;

    // wait export_done
    await waitUntil(async () => {
      const e = await api(`/export/${exportId}`);
      return e.status === "done";
    });

    const expDone = await api(`/export/${exportId}`);
    expect(expDone.file_url).toContain("http");

    // 5) trigger publish
    await api(`/cards/${cardId}/publish`, {
      method: "POST",
      body: JSON.stringify({
        export_id: exportId,
        correlation_id: `cid-pub-${cardId}`,
      }),
    });

    // wait published
    await waitUntil(async () => {
      const c = await api(`/cards/${cardId}`);
      return c.status === "published";
    }, 60000);

    const finalCard = await api(`/cards/${cardId}`);
    expect(finalCard.status).toBe("published");
    expect(finalCard.publish?.avito_item_id || finalCard.avito_item_id).toBeTruthy();
  });
});
