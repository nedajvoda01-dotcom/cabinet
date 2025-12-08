// frontend/src/features/publish/api.ts
import {
  PublishTasksListSchema,
  PublishTaskSchema,
  RunPublishRequestSchema,
  RetryPublishRequestSchema,
  CancelPublishRequestSchema,
  UnpublishRequestSchema,
} from "./schemas";

type HttpMethod = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

export interface PublishApiOptions {
  baseUrl?: string;                 // default: /api
  getToken?: () => string | null;
}

export class PublishApi {
  private baseUrl: string;
  private getToken?: () => string | null;

  constructor(opts: PublishApiOptions = {}) {
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
      const message = json?.message || json?.error || `PublishApi error ${res.status}`;
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
    channel?: string;
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
    return this.request(`/publish/tasks${qs}`, "GET", undefined, PublishTasksListSchema);
  }

  get(id: string | number) {
    return this.request(`/publish/tasks/${id}`, "GET", undefined, PublishTaskSchema);
  }

  run(input: unknown) {
    const payload = RunPublishRequestSchema.parse(input);
    return this.request(`/publish/run`, "POST", payload, PublishTaskSchema);
  }

  retry(id: string | number, input: unknown = {}) {
    const payload = RetryPublishRequestSchema.parse(input);
    return this.request(`/publish/tasks/${id}/retry`, "POST", payload, PublishTaskSchema);
  }

  cancel(id: string | number, input: unknown = {}) {
    const payload = CancelPublishRequestSchema.parse(input);
    return this.request(`/publish/tasks/${id}/cancel`, "POST", payload, PublishTaskSchema);
  }

  unpublish(id: string | number, input: unknown = {}) {
    const payload = UnpublishRequestSchema.parse(input);
    return this.request(`/publish/tasks/${id}/unpublish`, "POST", payload, PublishTaskSchema);
  }

  getMetrics() {
    return this.request(`/publish/metrics`, "GET");
  }
}

export const publishApi = new PublishApi();
