// frontend/src/features/export/schemas.ts
import { z } from "zod";

export const TimestampSchema = z.string(); // ISO
export const IdSchema = z.union([z.number(), z.string()]);

export const ExportStatusSchema = z.enum([
  "queued",
  "processing",
  "ready",
  "failed",
  "canceled",
]);

export type ExportStatus = z.infer<typeof ExportStatusSchema>;

export const ExportFormatSchema = z.enum(["csv", "xlsx", "json"]).default("csv");
export type ExportFormat = z.infer<typeof ExportFormatSchema>;

export const ExportTaskSchema = z.object({
  id: IdSchema,
  card_id: IdSchema.optional(),          // if export for single card
  status: ExportStatusSchema,
  attempts: z.number().nonnegative().default(0),

  format: ExportFormatSchema.optional(),
  params: z.record(z.any()).optional(),

  file_url: z.string().url().optional(), // if backend returns url
  file_name: z.string().optional(),
  file_size: z.number().nonnegative().optional(),

  error_code: z.string().optional(),
  error_message: z.string().optional(),

  created_at: TimestampSchema,
  updated_at: TimestampSchema,
  finished_at: TimestampSchema.optional(),
});

export type ExportTask = z.infer<typeof ExportTaskSchema>;

export const ExportListSchema = z.object({
  total: z.number().nonnegative().optional(),
  items: z.array(ExportTaskSchema),
});

export type ExportList = z.infer<typeof ExportListSchema>;

// ----- create / retry / cancel -----

export const CreateExportRequestSchema = z.object({
  card_id: IdSchema.optional(),
  format: ExportFormatSchema.optional(),
  params: z.record(z.any()).optional(),
});

export type CreateExportRequest = z.infer<typeof CreateExportRequestSchema>;

export const CancelExportRequestSchema = z.object({
  reason: z.string().optional(),
});

export const RetryExportRequestSchema = z.object({
  reason: z.string().optional(),
  force: z.boolean().optional(),
});
