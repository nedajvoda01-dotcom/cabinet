export interface Actor {
  actorId: string;
  actorType: ActorType;
}

export type ActorId = string;

export enum ActorType {
  USER = 'user',
  INTEGRATION = 'integration',
}

export enum ErrorKind {
  VALIDATION_ERROR = 'validation_error',
  SECURITY_DENIED = 'security_denied',
  NOT_FOUND = 'not_found',
  INTERNAL_ERROR = 'internal_error',
  INTEGRATION_UNAVAILABLE = 'integration_unavailable',
  RATE_LIMITED = 'rate_limited',
}

export enum HierarchyRole {
  USER = 'user',
  ADMIN = 'admin',
  SUPER_ADMIN = 'super_admin',
}

export enum JobStatus {
  QUEUED = 'queued',
  RUNNING = 'running',
  SUCCEEDED = 'succeeded',
  FAILED = 'failed',
  DEAD_LETTER = 'dead_letter',
}

export enum PipelineStage {
  PARSE = 'parse',
  PHOTOS = 'photos',
  PUBLISH = 'publish',
  EXPORT = 'export',
  CLEANUP = 'cleanup',
}

export type Scope = string;

export interface TraceContext {
  requestId: string;
  timestamp?: string;
}

export function canonicalJson(value: unknown): string {
  return encodeValue(value);
}

function encodeValue(value: unknown): string {
  if (Array.isArray(value)) {
    const normalized = value.map((item) => JSON.parse(encodeValue(item)));
    return JSON.stringify(normalized);
  }

  if (value !== null && typeof value === "object") {
    const entries = Object.entries(value as Record<string, unknown>).sort(([a], [b]) => a.localeCompare(b));
    const normalized: Record<string, unknown> = {};
    for (const [key, item] of entries) {
      normalized[key] = JSON.parse(encodeValue(item));
    }
    return JSON.stringify(normalized);
  }

  return JSON.stringify(value);
}
