// cabinet/frontend/src/features/auth/schemas.ts
import { z } from "zod";

export const IdSchema = z.union([z.number(), z.string()]);

export const RoleSchema = z.enum(["operator", "admin", "viewer", "unknown"]).default("unknown");
export type Role = z.infer<typeof RoleSchema>;

export const UserSchema = z.object({
  id: IdSchema,
  email: z.string().email(),
  name: z.string().optional(),
  role: RoleSchema.optional(),
  is_active: z.boolean().optional(),
});

export type User = z.infer<typeof UserSchema>;

export const LoginRequestSchema = z.object({
  email: z.string().email(),
  password: z.string().min(1),
});

export type LoginRequest = z.infer<typeof LoginRequestSchema>;

export const LoginResponseSchema = z.object({
  access_token: z.string(),
  refresh_token: z.string().optional(),
  user: UserSchema,
});

export type LoginResponse = z.infer<typeof LoginResponseSchema>;

export const RefreshResponseSchema = z.object({
  access_token: z.string(),
});

export type RefreshResponse = z.infer<typeof RefreshResponseSchema>;
