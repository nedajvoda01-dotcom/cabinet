import { z } from "zod";

export const SettingSchema = z.object({ id: z.string(), label: z.string(), value: z.boolean() });
