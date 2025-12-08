// frontend/src/features/photos/schemas.ts
import { z } from "zod";

export const TimestampSchema = z.string(); // ISO
export const IdSchema = z.union([z.number(), z.string()]);

export const PhotosStatusSchema = z.enum([
  "queued",
  "processing",
  "ready",
  "failed",
  "canceled",
]);

export type PhotosStatus = z.infer<typeof PhotosStatusSchema>;

export const PhotosProgressSchema = z.object({
  step: z.string(),                 // e.g. download/mask/normalize/order
  done: z.number().nonnegative(),   // processed count
  total: z.number().nonnegative(),  // total count
  message: z.string().optional(),
});

export type PhotosProgress = z.infer<typeof PhotosProgressSchema>;

export const PhotoArtifactSchema = z.object({
  id: IdSchema,
  card_id: IdSchema.optional(),
  raw_url: z.string().url().optional(),
  masked_url: z.string().url().optional(),
  order: z.number().nonnegative().optional(),
  status: z.enum(["raw", "masked", "failed", "processing"]).optional(),
  meta: z.record(z.any()).optional(),
  created_at: TimestampSchema.optional(),
});

export type PhotoArtifact = z.infer<typeof PhotoArtifactSchema>;

export const PhotosTaskSchema = z.object({
  id: IdSchema,
  card_id: IdSchema.optional(),

  status: PhotosStatusSchema,
  attempts: z.number().nonnegative().default(0),

  payload_json: z.record(z.any()).optional(),  // input urls/rules
  result_json: z.record(z.any()).optional(),   // output photo set/order
  progress: z.array(PhotosProgressSchema).optional(),

  error_code: z.string().optional(),
  error_message: z.string().optional(),

  created_at: TimestampSchema,
  updated_at: TimestampSchema,
  finished_at: TimestampSchema.optional(),
});

export type PhotosTask = z.infer<typeof PhotosTaskSchema>;

export const PhotosTasksListSchema = z.object({
  total: z.number().nonnegative().optional(),
  items: z.array(PhotosTaskSchema),
});

export type PhotosTasksList = z.infer<typeof PhotosTasksListSchema>;

// ---------- create/run ----------
export const RunPhotosRequestSchema = z.object({
  card_id: IdSchema.optional(),
  payload: z.record(z.any()).optional(),
  rules: z.record(z.any()).optional(),
});

export type RunPhotosRequest = z.infer<typeof RunPhotosRequestSchema>;

export const RetryPhotosRequestSchema = z.object({
  reason: z.string().optional(),
  force: z.boolean().optional(),
});

export const CancelPhotosRequestSchema = z.object({
  reason: z.string().optional(),
});

// ---------- artifacts ----------
export const PhotosListForCardSchema = z.object({
  card_id: IdSchema,
  items: z.array(PhotoArtifactSchema),
});

export type PhotosListForCard = z.infer<typeof PhotosListForCardSchema>;

export const SetPrimaryRequestSchema = z.object({
  photo_id: IdSchema,
});

export const ReorderPhotosRequestSchema = z.object({
  ordered_photo_ids: z.array(IdSchema).min(1),
});
