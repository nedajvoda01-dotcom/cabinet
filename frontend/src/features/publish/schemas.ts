// frontend/src/features/publish/schemas.ts
import { z } from "zod";

export const TimestampSchema = z.string(); // ISO
export const IdSchema = z.union([z.number(), z.string()]);

export const PublishStatusSchema = z.enum([
  "queued",
  "processing",
  "ready",
  "failed",
  "canceled",
]);

export type PublishStatus = z.infer<typeof PublishStatusSchema>;

export const PublishChannelSchema = z.string(); // e.g. avito, drom, site, etc.
export type PublishChannel = z.infer<typeof PublishChannelSchema>;

export const PublishProgressSchema = z.object({
  step: z.string(),                 // validate/upload/create_listing/verify
  done: z.number().nonnegative(),
  total: z.number().nonnegative(),
  message: z.string().optional(),
});
export type PublishProgress = z.infer<typeof PublishProgressSchema>;

export const PublishRefSchema = z.object({
  channel: PublishChannelSchema,
  external_id: z.string(),
  url: z.string().url().optional(),
  meta: z.record(z.any()).optional(),
});
export type PublishRef = z.infer<typeof PublishRefSchema>;

export const PublishTaskSchema = z.object({
  id: IdSchema,
  card_id: IdSchema.optional(),
  channel: PublishChannelSchema.optional(),

  status: PublishStatusSchema,
  attempts: z.number().nonnegative().default(0),

  payload_json: z.record(z.any()).optional(),  // input: export_ref, rules, etc.
  result_json: z.record(z.any()).optional(),   // output full result (debug)
  progress: z.array(PublishProgressSchema).optional(),

  publish_refs: z.array(PublishRefSchema).default([]),

  error_code: z.string().optional(),
  error_message: z.string().optional(),

  created_at: TimestampSchema,
  updated_at: TimestampSchema,
  finished_at: TimestampSchema.optional(),
});

export type PublishTask = z.infer<typeof PublishTaskSchema>;

export const PublishTasksListSchema = z.object({
  total: z.number().nonnegative().optional(),
  items: z.array(PublishTaskSchema),
});

export type PublishTasksList = z.infer<typeof PublishTasksListSchema>;

// ---------- run / retry / cancel / unpublish ----------
export const RunPublishRequestSchema = z.object({
  card_id: IdSchema.optional(),
  channel: PublishChannelSchema.optional(),
  payload: z.record(z.any()).optional(),  // export_ref, settings
  rules: z.record(z.any()).optional(),
});

export type RunPublishRequest = z.infer<typeof RunPublishRequestSchema>;

export const RetryPublishRequestSchema = z.object({
  reason: z.string().optional(),
  force: z.boolean().optional(),
});

export const CancelPublishRequestSchema = z.object({
  reason: z.string().optional(),
});

export const UnpublishRequestSchema = z.object({
  reason: z.string().optional(),
});
