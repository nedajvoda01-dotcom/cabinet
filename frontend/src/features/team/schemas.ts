import { z } from "zod";

export const MemberSchema = z.object({ id: z.string(), email: z.string().email(), role: z.string() });
