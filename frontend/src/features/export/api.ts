// frontend/src/features/export/api.ts
import {
  ExportListSchema,
  ExportTaskSchema,
  CreateExportRequestSchema,
  CancelExportRequestSchema,
  RetryExportRequestSchema,
} from "./schemas";

type HttpMethod = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

export interface ExportApiOptions {
  baseUrl?: string;                 // default: /api
  getToken?: () => string | null;
}

export class ExportApi {
  private baseUrl: string;
  private getToken?: () => string | null;

  constructor(opts: ExportApiOptions = {}) {
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
      const message = json?.message || json?.error || `ExportApi error ${res.status}`;
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
    from_ts?: number;
    to_ts?: number;
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
    return this.request(`/export${qs}`, "GET", undefined, ExportListSchema);
  }

  get(id: string | number) {
    return this.request(`/export/${id}`, "GET", undefined, ExportTaskSchema);
  }

  create(input: unknown) {
    const payload = CreateExportRequestSchema.parse(input);
    return this.request(`/export`, "POST", payload, ExportTaskSchema);
  }

  cancel(id: string | number, input: unknown = {}) {
    const payload = CancelExportRequestSchema.parse(input);
    return this.request(`/export/${id}/cancel`, "POST", payload, ExportTaskSchema);
  }

  retry(id: string | number, input: unknown = {}) {
    const payload = RetryExportRequestSchema.parse(input);
    return this.request(`/export/${id}/retry`, "POST", payload, ExportTaskSchema);
  }

  async download(id: string | number): Promise<Blob> {
    const headers: Record<string, string> = {};
    const token = this.getToken?.();
    if (token) headers["Authorization"] = `Bearer ${token}`;

    const res = await fetch(`${this.baseUrl}/export/${id}/download`, {
      method: "GET",
      headers,
    });

    if (!res.ok) {
      let json: any = {};
      try { json = await res.json(); } catch {}
      throw new Error(json?.message || `Download error ${res.status}`);
    }

    return await res.blob();
  }
}

export const exportApi = new ExportApi();
