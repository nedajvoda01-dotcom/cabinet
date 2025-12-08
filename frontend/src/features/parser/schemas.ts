// frontend/src/features/parser/schemas.ts
import { z } from "zod";

export const TimestampSchema = z.string(); // ISO
export const IdSchema = z.union([z.number(), z.string()]);

export const ParserStatusSchema = z.enum([
  "queued",
  "processing",
  "ready",
  "failed",
  "canceled",
]);

export type ParserStatus = z.infer<typeof ParserStatusSchema>;

/**
 * ParserTask:
 * - payload_json: что парсим (сырой источник/HTML/url/metadata)
 * - result_json: нормализованный snapshot карточки (если ready)
 */
export const ParserTaskSchema = z.object({
  id: IdSchema,
  card_id: IdSchema.optional(),
  source: z.string().optional(),
  source_id: z.string().optional(),

  status: ParserStatusSchema,
  attempts: z.number().nonnegative().default(0),

  payload_json: z.record(z.any()).optional(),
  result_json: z.record(z.any()).optional(),

  error_code: z.string().optional(),
  error_message: z.string().optional(),

  created_at: TimestampSchema,
  updated_at: TimestampSchema,
  finished_at: TimestampSchema.optional(),
});

export type ParserTask = z.infer<typeof ParserTaskSchema>;

export const ParserTasksListSchema = z.object({
  total: z.number().nonnegative().optional(),
  items: z.array(ParserTaskSchema),
});

export type ParserTasksList = z.infer<typeof ParserTasksListSchema>;

// ---------- create/run ----------
export const RunParserRequestSchema = z.object({
  card_id: IdSchema.optional(),
  source: z.string().optional(),
  source_id: z.string().optional(),
  payload: z.record(z.any()).optional(),
});

export type RunParserRequest = z.infer<typeof RunParserRequestSchema>;

export const RetryParserRequestSchema = z.object({
  reason: z.string().optional(),
  force: z.boolean().optional(),
});
