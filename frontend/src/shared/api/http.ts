// cabinet/frontend/src/shared/api/http.ts
import { ApiError } from "./errors";

export type HttpMethod = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

export interface HttpClientOptions {
  baseUrl?: string;                 // default: /api
  getToken?: () => string | null;   // inject auth
  onUnauthorized?: () => void;      // optional handler for 401
}

/**
 * Единый клиент для всех features.
 * Поддерживает zod-schemas через schema.parse()
 */
export class HttpClient {
  baseUrl: string;
  getToken?: () => string | null;
  onUnauthorized?: () => void;

  constructor(opts: HttpClientOptions = {}) {
    this.baseUrl = opts.baseUrl ?? "/api";
    this.getToken = opts.getToken;
    this.onUnauthorized = opts.onUnauthorized;
  }

  async request<T>(
    path: string,
    method: HttpMethod,
    body?: unknown,
    schema?: { parse: (x: unknown) => T },
    extraHeaders?: Record<string, string>
  ): Promise<T> {
    const headers: Record<string, string> = {
      "Content-Type": "application/json",
      ...(extraHeaders ?? {}),
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
      if (res.status === 401) this.onUnauthorized?.();

      const message =
        json?.message ||
        json?.error ||
        `HTTP ${res.status} on ${path}`;

      throw new ApiError(message, {
        status: res.status,
        payload: json,
        path,
      });
    }

    return schema ? schema.parse(json) : (json as T);
  }

  get<T>(path: string, schema?: { parse: (x: unknown) => T }) {
    return this.request<T>(path, "GET", undefined, schema);
  }
  post<T>(path: string, body?: unknown, schema?: { parse: (x: unknown) => T }) {
    return this.request<T>(path, "POST", body, schema);
  }
  patch<T>(path: string, body?: unknown, schema?: { parse: (x: unknown) => T }) {
    return this.request<T>(path, "PATCH", body, schema);
  }
  delete<T>(path: string, schema?: { parse: (x: unknown) => T }) {
    return this.request<T>(path, "DELETE", undefined, schema);
  }

  // Для blob-download (export)
  async download(path: string, fileName?: string): Promise<Blob> {
    const headers: Record<string, string> = {};
    const token = this.getToken?.();
    if (token) headers["Authorization"] = `Bearer ${token}`;

    const res = await fetch(`${this.baseUrl}${path}`, { method: "GET", headers });

    if (!res.ok) {
      let json: any = {};
      try { json = await res.json(); } catch {}
      throw new ApiError(json?.message || `Download HTTP ${res.status}`, {
        status: res.status,
        payload: json,
        path,
      });
    }

    const blob = await res.blob();

    // если передали fileName — можно сразу инициировать скачивание
    if (fileName) {
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = fileName;
      a.click();
      URL.revokeObjectURL(url);
    }

    return blob;
  }
}

// default singleton (app может подменить getToken/onUnauthorized)
export const http = new HttpClient();
