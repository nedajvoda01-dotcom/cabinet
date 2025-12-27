import { z } from "zod";

export const UserRowSchema = z.object({ id: z.string(), email: z.string().email(), role: z.string() });
