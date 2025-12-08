// cabinet/frontend/src/features/admin/api.ts
import { http } from "../../shared/api/http";
import {
  QueuesListSchema,
  QueueJobsSchema,
  DlqListSchema,
  DlqItemSchema,
  DlqRetryRequestSchema,
  DlqBulkRetryRequestSchema,
  LogsListSchema,
  HealthSchema,
} from "./schemas";

export class AdminApi {
  // ---- queues ----
  listQueues() {
    return http.get("/admin/queues", QueuesListSchema);
  }

  listQueueJobs(type: string, params?: { limit?: number; offset?: number; status?: string }) {
    const sp = new URLSearchParams();
    if (params) {
      for (const [k, v] of Object.entries(params)) {
        if (v != null) sp.set(k, String(v));
      }
    }
    const qs = sp.toString() ? `?${sp.toString()}` : "";
    return http.get(`/admin/queues/${type}/jobs${qs}`, QueueJobsSchema);
  }

  pauseQueue(type: string) {
    return http.post(`/admin/queues/${type}/pause`, {});
  }

  resumeQueue(type: string) {
    return http.post(`/admin/queues/${type}/resume`, {});
  }

  // ---- dlq ----
  listDlq(params?: { limit?: number; offset?: number; type?: string }) {
    const sp = new URLSearchParams();
    if (params) {
      for (const [k, v] of Object.entries(params)) {
        if (v != null) sp.set(k, String(v));
      }
    }
    const qs = sp.toString() ? `?${sp.toString()}` : "";
    return http.get(`/admin/dlq${qs}`, DlqListSchema);
  }

  getDlq(id: string | number) {
    return http.get(`/admin/dlq/${id}`, DlqItemSchema);
  }

  retryDlq(id: string | number, input: unknown = {}) {
    const payload = DlqRetryRequestSchema.parse(input);
    return http.post(`/admin/dlq/${id}/retry`, payload);
  }

  bulkRetryDlq(input: unknown) {
    const payload = DlqBulkRetryRequestSchema.parse(input);
    return http.post(`/admin/dlq/bulk-retry`, payload);
  }

  // ---- logs ----
  listLogs(params?: { limit?: number; offset?: number; level?: string; q?: string }) {
    const sp = new URLSearchParams();
    if (params) {
      for (const [k, v] of Object.entries(params)) {
        if (v != null) sp.set(k, String(v));
      }
    }
    const qs = sp.toString() ? `?${sp.toString()}` : "";
    return http.get(`/admin/logs${qs}`, LogsListSchema);
  }

  // ---- health / integrations ----
  getHealth() {
    return http.get(`/admin/health`, HealthSchema);
  }
}

export const adminApi = new AdminApi();
