// cabinet/frontend/src/features/admin/schemas.ts
import { z } from "zod";

// ---------- Common ----------
export const TimestampSchema = z.string(); // ISO
export const IdSchema = z.union([z.number(), z.string()]);

// ---------- Dashboard ----------
export const QueueKpiSchema = z.object({
  type: z.string(),            // photos/export/publish/parser/status/...
  depth: z.number().nonnegative(),
  rate_per_min: z.number().nonnegative().optional(),
  avg_latency_ms: z.number().nonnegative().optional(),
  retrying: z.number().nonnegative().optional(),
  paused: z.boolean().optional(),
});

export const DashboardKpiSchema = z.object({
  throughput_per_min: z.number().nonnegative(),
  retries_percent: z.number().min(0).max(100),
  dlq_growth_24h: z.number(),              // can be negative
  errors_top: z.array(z.object({
    code: z.string(),
    message: z.string().optional(),
    count: z.number().nonnegative(),
  })),
  queues: z.array(QueueKpiSchema),
  integrations: z.array(z.object({
    service: z.string(),
    status: z.enum(["ok", "fail", "degraded", "unknown"]),
    latency_ms: z.number().nonnegative().optional(),
    updated_at: TimestampSchema.optional(),
    last_error_code: z.string().optional(),
    last_error_message: z.string().optional(),
  })),
});

export type DashboardKpi = z.infer<typeof DashboardKpiSchema>;
export type QueueKpi = z.infer<typeof QueueKpiSchema>;

// ---------- Queues ----------
export const QueuesListSchema = z.object({
  items: z.array(QueueKpiSchema),
});
export type QueuesList = z.infer<typeof QueuesListSchema>;

// ---------- DLQ ----------
export const DlqJobSchema = z.object({
  id: IdSchema,
  queue_type: z.string(),
  reason_code: z.string(),
  reason_message: z.string().optional(),
  payload_json: z.record(z.any()).optional(),
  attempts: z.number().nonnegative(),
  service_source: z.string().optional(),
  card_ids: z.array(IdSchema).optional(),
  created_at: TimestampSchema,
});

export const DlqListSchema = z.object({
  total: z.number().nonnegative(),
  items: z.array(DlqJobSchema),
});

export const DlqRetryRequestSchema = z.object({
  dlq_id: IdSchema,
});

export const DlqBulkRetryRequestSchema = z.object({
  dlq_ids: z.array(IdSchema).min(1),
});

export const DlqDropRequestSchema = z.object({
  dlq_id: IdSchema,
});

export type DlqJob = z.infer<typeof DlqJobSchema>;
export type DlqList = z.infer<typeof DlqListSchema>;
export type DlqRetryRequest = z.infer<typeof DlqRetryRequestSchema>;
export type DlqBulkRetryRequest = z.infer<typeof DlqBulkRetryRequestSchema>;
export type DlqDropRequest = z.infer<typeof DlqDropRequestSchema>;

// ---------- Logs ----------
export const LogItemSchema = z.object({
  id: IdSchema,
  level: z.enum(["debug", "info", "warn", "error", "fatal"]).optional(),
  action: z.string().optional(),
  message: z.string(),
  code: z.string().optional(),
  user_id: IdSchema.optional(),
  card_id: IdSchema.optional(),
  correlation_id: z.string().optional(),
  meta_json: z.record(z.any()).optional(),
  ts: TimestampSchema,
});

export const LogsListSchema = z.object({
  total: z.number().nonnegative(),
  items: z.array(LogItemSchema),
});

export type LogItem = z.infer<typeof LogItemSchema>;
export type LogsList = z.infer<typeof LogsListSchema>;

// ---------- Health / Integrations ----------
export const HealthSchema = z.object({
  ok: z.boolean(),
  services: z.array(z.object({
    service: z.string(),
    status: z.enum(["ok", "fail", "degraded", "unknown"]),
    latency_ms: z.number().nonnegative().optional(),
    updated_at: TimestampSchema.optional(),
    last_error_code: z.string().optional(),
    last_error_message: z.string().optional(),
  })),
});

export type Health = z.infer<typeof HealthSchema>;
