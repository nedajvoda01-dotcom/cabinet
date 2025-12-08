// frontend/src/features/photos/api.ts
import {
  PhotosTasksListSchema,
  PhotosTaskSchema,
  RunPhotosRequestSchema,
  RetryPhotosRequestSchema,
  CancelPhotosRequestSchema,
  PhotosListForCardSchema,
  SetPrimaryRequestSchema,
  ReorderPhotosRequestSchema,
} from "./schemas";

type HttpMethod = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

export interface PhotosApiOptions {
  baseUrl?: string;                 // default: /api
  getToken?: () => string | null;
}

export class PhotosApi {
  private baseUrl: string;
  private getToken?: () => string | null;

  constructor(opts: PhotosApiOptions = {}) {
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
      const message = json?.message || json?.error || `PhotosApi error ${res.status}`;
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
    return this.request(`/photos/tasks${qs}`, "GET", undefined, PhotosTasksListSchema);
  }

  get(id: string | number) {
    return this.request(`/photos/tasks/${id}`, "GET", undefined, PhotosTaskSchema);
  }

  run(input: unknown) {
    const payload = RunPhotosRequestSchema.parse(input);
    return this.request(`/photos/run`, "POST", payload, PhotosTaskSchema);
  }

  retry(id: string | number, input: unknown = {}) {
    const payload = RetryPhotosRequestSchema.parse(input);
    return this.request(`/photos/tasks/${id}/retry`, "POST", payload, PhotosTaskSchema);
  }

  cancel(id: string | number, input: unknown = {}) {
    const payload = CancelPhotosRequestSchema.parse(input);
    return this.request(`/photos/tasks/${id}/cancel`, "POST", payload, PhotosTaskSchema);
  }

  // -------- artifacts --------
  listForCard(cardId: string | number) {
    return this.request(`/photos/card/${cardId}`, "GET", undefined, PhotosListForCardSchema);
  }

  deletePhoto(photoId: string | number) {
    return this.request(`/photos/${photoId}`, "DELETE");
  }

  setPrimary(cardId: string | number, input: unknown) {
    const payload = SetPrimaryRequestSchema.parse(input);
    return this.request(`/photos/card/${cardId}/primary`, "POST", payload);
  }

  reorder(cardId: string | number, input: unknown) {
    const payload = ReorderPhotosRequestSchema.parse(input);
    return this.request(`/photos/card/${cardId}/reorder`, "POST", payload);
  }
}

export const photosApi = new PhotosApi();
