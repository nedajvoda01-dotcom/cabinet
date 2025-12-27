import { z } from "zod";

export const ChartSchema = z.object({ id: z.string(), title: z.string() });
