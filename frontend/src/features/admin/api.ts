// cabinet/frontend/src/features/admin/api.ts
import {
  DashboardKpiSchema,
  QueuesListSchema,
  DlqListSchema,
  LogsListSchema,
  HealthSchema,
  DlqRetryRequestSchema,
  DlqBulkRetryRequestSchema,
  DlqDropRequestSchema,
} from "./schemas";

type HttpMethod = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

export interface AdminApiOptions {
  baseUrl?: string;                 // default: /api
  getToken?: () => string | null;   // inject auth
}

export class AdminApi {
  private baseUrl: string;
  private getToken?: () => string | null;

  constructor(opts: AdminApiOptions = {}) {
    this.baseUrl = opts.baseUrl ?? "/api";
    this.getToken = opts.getToken;
  }

  // --- core request ---
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
      const message =
        json?.message ||
        json?.error ||
        `AdminApi error ${res.status} on ${path}`;
      const err: any = new Error(message);
      err.status = res.status;
      err.payload = json;
      throw err;
    }

    return schema ? schema.parse(json) : (json as T);
  }

  // --- dashboard ---
  getDashboard() {
    return this.request("/admin/dashboard", "GET", undefined, DashboardKpiSchema);
  }

  // --- queues ---
  getQueues() {
    return this.request("/admin/queues", "GET", undefined, QueuesListSchema);
  }

  pauseQueue(type: string) {
    return this.request(`/admin/queues/${encodeURIComponent(type)}/pause`, "POST");
  }

  resumeQueue(type: string) {
    return this.request(`/admin/queues/${encodeURIComponent(type)}/resume`, "POST");
  }

  // --- dlq ---
  getDlq() {
    return this.request("/admin/dlq", "GET", undefined, DlqListSchema);
  }

  retryDlq(input: unknown) {
    const payload = DlqRetryRequestSchema.parse(input);
    return this.request("/admin/dlq/retry", "POST", payload);
  }

  retryDlqBulk(input: unknown) {
    const payload = DlqBulkRetryRequestSchema.parse(input);
    return this.request("/admin/dlq/retry-bulk", "POST", payload);
  }

  dropDlq(input: unknown) {
    const payload = DlqDropRequestSchema.parse(input);
    return this.request("/admin/dlq/drop", "POST", payload);
  }

  // --- logs ---
  getLogs(params?: {
    user_id?: string | number;
    card_id?: string | number;
    action?: string;
    q?: string;
    from_ts?: string;
    to_ts?: string;
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
    return this.request(`/admin/logs${qs}`, "GET", undefined, LogsListSchema);
  }

  // --- health / integrations ---
  getHealth() {
    return this.request("/admin/health", "GET", undefined, HealthSchema);
  }

  testIntegration(service: string) {
    return this.request(
      `/admin/integrations/${encodeURIComponent(service)}/test`,
      "POST"
    );
  }
}

// default singleton (можно переопределить в app)
export const adminApi = new AdminApi();
