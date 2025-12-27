import { z } from "zod";

export const IdSchema = z.union([z.number(), z.string()]);

export const RoleSchema = z.enum(["superadmin", "admin", "member", "guest"]).default("guest");
export type Role = z.infer<typeof RoleSchema>;

export const UserSchema = z.object({
  id: IdSchema,
  email: z.string().email(),
  name: z.string().optional(),
  role: RoleSchema.optional(),
  status: z.enum(["pending", "approved", "rejected"]).optional(),
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
