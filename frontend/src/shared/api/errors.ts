// cabinet/frontend/src/shared/api/errors.ts

export class ApiError extends Error {
  status?: number;
  payload?: any;
  path?: string;

  constructor(message: string, opts?: { status?: number; payload?: any; path?: string }) {
    super(message);
    this.name = "ApiError";
    this.status = opts?.status;
    this.payload = opts?.payload;
    this.path = opts?.path;
  }
}

export function isApiError(e: unknown): e is ApiError {
  return e instanceof ApiError;
}

export function normalizeError(e: unknown): ApiError {
  if (isApiError(e)) return e;
  if (e instanceof Error) return new ApiError(e.message);
  return new ApiError(String(e));
}
