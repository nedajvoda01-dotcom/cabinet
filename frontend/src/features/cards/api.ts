// cabinet/frontend/src/features/cards/api.ts
import {
  CardsListSchema,
  CardSchema,
  CreateCardRequestSchema,
  UpdateCardRequestSchema,
} from "./schemas";

type HttpMethod = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

export interface CardsApiOptions {
  baseUrl?: string;                 // default: /api
  getToken?: () => string | null;
}

export class CardsApi {
  private baseUrl: string;
  private getToken?: () => string | null;

  constructor(opts: CardsApiOptions = {}) {
    this.baseUrl = opts.baseUrl ?? "/api";
    this.getToken = opts.getToken;
  }

  private async request<T>(
    path: string,
    method: HttpMethod,
    body?: unknown,
    schema?: { parse: (x: unknown) => T }
  ): Promise<T> {
    const headers: Record<string, string> = {
      "Content-Type": "application/json",
    };
    const token = this.getToken?.();
    if (token) headers["Authorization"] = `Bearer ${token}`;

    const res = await fetch(`${this.baseUrl}${path}`, {
      method,
      headers,
      body: body == null ? undefined : JSON.stringify(body),
    });

    const json = await res.json().catch(() => ({}));
    if (!res.ok) {
      const message = json?.message || json?.error || `CardsApi error ${res.status}`;
      const err: any = new Error(message);
      err.status = res.status;
      err.payload = json;
      throw err;
    }

    return schema ? schema.parse(json) : (json as T);
  }

  // ---------- CRUD ----------
  list(params?: {
    status?: string;
    q?: string;
    limit?: number;
    offset?: number;
  }) {
    const sp = new URLSearchParams();
    if (params) {
      Object.entries(params).forEach(([k, v]) => {
        if (v != null) sp.set(k, String(v));
      });
    }
    const qs = sp.toString() ? `?${sp.toString()}` : "";
    return this.request(`/cards${qs}`, "GET", undefined, CardsListSchema);
  }

  get(id: string | number) {
    return this.request(`/cards/${id}`, "GET", undefined, CardSchema);
  }

  create(input: unknown) {
    const payload = CreateCardRequestSchema.parse(input);
    return this.request(`/cards`, "POST", payload, CardSchema);
  }

  update(id: string | number, input: unknown) {
    const payload = UpdateCardRequestSchema.parse(input);
    return this.request(`/cards/${id}`, "PATCH", payload, CardSchema);
  }

  delete(id: string | number) {
    return this.request(`/cards/${id}`, "DELETE");
  }

  // ---------- Pipeline triggers ----------
  startOrRetryPhotos(id: string | number) {
    return this.request(`/cards/${id}/photos`, "POST");
  }

  startOrRetryExport(id: string | number) {
    return this.request(`/cards/${id}/export`, "POST");
  }

  startOrRetryPublish(id: string | number) {
    return this.request(`/cards/${id}/publish`, "POST");
  }
}

export const cardsApi = new CardsApi();
