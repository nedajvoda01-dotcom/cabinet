// cabinet/frontend/src/features/cards/schemas.ts
import { z } from "zod";

// ---------- Common ----------
export const TimestampSchema = z.string(); // ISO
export const IdSchema = z.union([z.number(), z.string()]);

// ---------- Photos ----------
export const CardPhotoSchema = z.object({
  id: IdSchema,
  raw_url: z.string().url().optional(),
  masked_url: z.string().url().optional(),
  order: z.number().nonnegative().optional(),
  status: z.enum(["raw", "masked", "failed", "processing"]).optional(),
});

export type CardPhoto = z.infer<typeof CardPhotoSchema>;

// ---------- Card core ----------
export const VehicleSchema = z.object({
  make: z.string(),
  model: z.string(),
  year: z.number().int().optional(),
  body: z.string().optional(),
  mileage: z.number().nonnegative().optional(),
  vin: z.string().optional(),
});

export const PriceSchema = z.object({
  value: z.number().nonnegative(),
  currency: z.string().default("RUB"),
});

export const LocationSchema = z.object({
  city: z.string(),
  address: z.string().optional(),
  coords: z.object({
    lat: z.number(),
    lng: z.number(),
  }).optional(),
});

// StateMachine statuses (MVP)
export const CardStatusSchema = z.enum([
  "draft",

  "photos_queued",
  "photos_processing",
  "photos_ready",
  "photos_failed",

  "ready_for_export",
  "export_queued",
  "export_processing",
  "export_ready",
  "export_failed",

  "ready_for_publish",
  "publish_queued",
  "publish_processing",
  "published",
  "publish_failed",

  "blocked",
]);

export type CardStatus = z.infer<typeof CardStatusSchema>;

export const CardSchema = z.object({
  id: IdSchema,
  source: z.string(),
  source_id: z.string().optional(),
  status: CardStatusSchema,

  vehicle: VehicleSchema,
  price: PriceSchema.optional(),
  location: LocationSchema.optional(),
  description: z.string().optional(),

  photos: z.array(CardPhotoSchema).default([]),

  export_refs: z.array(IdSchema).default([]),
  publish_refs: z.array(IdSchema).default([]),

  last_error_code: z.string().optional(),
  last_error_message: z.string().optional(),

  created_at: TimestampSchema,
  updated_at: TimestampSchema,
});

export type Card = z.infer<typeof CardSchema>;

// ---------- lists ----------
export const CardsListSchema = z.object({
  total: z.number().nonnegative().optional(),
  items: z.array(CardSchema),
});

export type CardsList = z.infer<typeof CardsListSchema>;

// ---------- create/update ----------
export const CreateCardRequestSchema = z.object({
  source: z.string(),
  source_id: z.string().optional(),
  vehicle: VehicleSchema,
  price: PriceSchema.optional(),
  location: LocationSchema.optional(),
  description: z.string().optional(),
});

export const UpdateCardRequestSchema = z.object({
  vehicle: VehicleSchema.partial().optional(),
  price: PriceSchema.partial().optional(),
  location: LocationSchema.partial().optional(),
  description: z.string().optional(),

  // manual status reset only for admin, UI won't expose
  status: CardStatusSchema.optional(),
});

export type CreateCardRequest = z.infer<typeof CreateCardRequestSchema>;
export type UpdateCardRequest = z.infer<typeof UpdateCardRequestSchema>;
