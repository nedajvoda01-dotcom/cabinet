// frontend/src/features/parser/api.ts
import {
  ParserTasksListSchema,
  ParserTaskSchema,
  RunParserRequestSchema,
  RetryParserRequestSchema,
} from "./schemas";

type HttpMethod = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

export interface ParserApiOptions {
  baseUrl?: string;                 // default: /api
  getToken?: () => string | null;
}

export class ParserApi {
  private baseUrl: string;
  private getToken?: () => string | null;

  constructor(opts: ParserApiOptions = {}) {
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
      const message = json?.message || json?.error || `ParserApi error ${res.status}`;
      const err: any = new Error(message);
      err.status = res.status;
      err.payload = json;
      throw err;
    }

    return schema ? schema.parse(json) : (json as T);
  }

  // -------- tasks --------

  list(params?: {
    status?: string;
    card_id?: string | number;
    source?: string;
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
    return this.request(`/parser/tasks${qs}`, "GET", undefined, ParserTasksListSchema);
  }

  get(id: string | number) {
    return this.request(`/parser/tasks/${id}`, "GET", undefined, ParserTaskSchema);
  }

  run(input: unknown) {
    const payload = RunParserRequestSchema.parse(input);
    return this.request(`/parser/run`, "POST", payload, ParserTaskSchema);
  }

  retry(id: string | number, input: unknown = {}) {
    const payload = RetryParserRequestSchema.parse(input);
    return this.request(`/parser/tasks/${id}/retry`, "POST", payload, ParserTaskSchema);
  }

  // cancel endpoint optional; if backend doesn't have it, UI just won't call
  cancel(id: string | number, input: unknown = {}) {
    return this.request(`/parser/tasks/${id}/cancel`, "POST", input, ParserTaskSchema);
  }
}

export const parserApi = new ParserApi();
