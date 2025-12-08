// cabinet/frontend/src/features/admin/schemas.ts
import { z } from "zod";

export const IdSchema = z.union([z.number(), z.string()]);
export const TimestampSchema = z.string(); // ISO

// ---------- Queues ----------
export const QueueTypeSchema = z.string(); // photos/export/publish/parser/etc
export type QueueType = z.infer<typeof QueueTypeSchema>;

export const QueueDTO = z.object({
  type: QueueTypeSchema,
  depth: z.number().nonnegative().default(0),
  paused: z.boolean().default(false),
  in_flight: z.number().nonnegative().optional(),
  retries_24h: z.number().nonnegative().optional(),
  dlq_24h: z.number().nonnegative().optional(),
  meta: z.record(z.any()).optional(),
});

export type QueueDTO = z.infer<typeof QueueDTO>;

export const QueuesListSchema = z.object({
  items: z.array(QueueDTO),
});
export type QueuesList = z.infer<typeof QueuesListSchema>;

// JobDTO (Spec explicitly listed)
export const JobStatusSchema = z.enum([
  "queued",
  "processing",
  "ready",
  "failed",
  "canceled",
  "dlq",
]).catchall(z.string()); // allow future statuses
export type JobStatus = z.infer<typeof JobStatusSchema>;

export const JobErrorSchema = z.object({
  code: z.string().optional(),
  message: z.string().optional(),
});

export const JobDTO = z.object({
  job_id: IdSchema,
  type: QueueTypeSchema,
  status: JobStatusSchema.optional(),
  card_id: IdSchema.optional(),

  attempts: z.number().nonnegative().default(0),
  next_retry_at: TimestampSchema.optional(),

  last_error: JobErrorSchema.optional(),
  payload_json: z.record(z.any()).optional(),

  created_at: TimestampSchema.optional(),
  updated_at: TimestampSchema.optional(),
});

export type JobDTO = z.infer<typeof JobDTO>;

export const QueueJobsSchema = z.object({
  type: QueueTypeSchema,
  total: z.number().nonnegative().optional(),
  items: z.array(JobDTO),
});
export type QueueJobs = z.infer<typeof QueueJobsSchema>;

// ---------- DLQ ----------
export const DlqItemSchema = z.object({
  id: IdSchema,
  job_id: IdSchema,
  type: QueueTypeSchema,
  status: z.literal("dlq").optional(),
  attempts: z.number().nonnegative().default(0),
  last_error: JobErrorSchema.optional(),
  payload_json: z.record(z.any()).optional(),
  created_at: TimestampSchema,
  updated_at: TimestampSchema.optional(),
});

export type DlqItem = z.infer<typeof DlqItemSchema>;

export const DlqListSchema = z.object({
  total: z.number().nonnegative().optional(),
  items: z.array(DlqItemSchema),
});
export type DlqList = z.infer<typeof DlqListSchema>;

// retry payloads
export const DlqRetryRequestSchema = z.object({
  reason: z.string().optional(),
  force: z.boolean().optional(),
});

export const DlqBulkRetryRequestSchema = z.object({
  ids: z.array(IdSchema).min(1),
  reason: z.string().optional(),
  force: z.boolean().optional(),
});

// ---------- Logs ----------
export const LogLevelSchema = z.enum(["debug", "info", "warn", "error"]).catchall(z.string());
export type LogLevel = z.infer<typeof LogLevelSchema>;

export const LogEntrySchema = z.object({
  id: IdSchema.optional(),
  level: LogLevelSchema,
  message: z.string(),
  context: z.record(z.any()).optional(),
  correlation_id: z.string().optional(),
  type: QueueTypeSchema.optional(),
  card_id: IdSchema.optional(),
  created_at: TimestampSchema,
});

export type LogEntry = z.infer<typeof LogEntrySchema>;

export const LogsListSchema = z.object({
  total: z.number().nonnegative().optional(),
  items: z.array(LogEntrySchema),
});
export type LogsList = z.infer<typeof LogsListSchema>;

// ---------- Health / Integrations ----------
export const IntegrationHealthSchema = z.object({
  name: z.string(), // parser/photo-api/storage/robot/dolphin/avito
  ok: z.boolean(),
  latency_ms: z.number().nonnegative().optional(),
  last_error: JobErrorSchema.optional(),
  meta: z.record(z.any()).optional(),
  updated_at: TimestampSchema.optional(),
});

export type IntegrationHealth = z.infer<typeof IntegrationHealthSchema>;

export const HealthSchema = z.object({
  ok: z.boolean().default(true),
  integrations: z.array(IntegrationHealthSchema).default([]),
  kpi: z.record(z.any()).optional(), // throughput/retries/dlq growth etc.
  updated_at: TimestampSchema.optional(),
});

export type Health = z.infer<typeof HealthSchema>;
