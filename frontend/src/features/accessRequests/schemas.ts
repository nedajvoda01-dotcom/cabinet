import { z } from "zod";

export const AccessRequestSchema = z.object({
  id: z.string(),
  email: z.string().email(),
  status: z.enum(["pending", "approved", "rejected"]),
});
