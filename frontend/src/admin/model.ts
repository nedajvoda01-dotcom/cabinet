// cabinet/frontend/src/features/admin/model.ts
import type {
  QueueDTO,
  QueueType,
  JobDTO,
  DlqItem,
  Health,
  IntegrationHealth,
  LogsList,
  LogEntry,
} from "./schemas";

export type {
  QueueDTO,
  QueueType,
  JobDTO,
  DlqItem,
  Health,
  IntegrationHealth,
  LogsList,
  LogEntry,
};

export function queueVariant(q: QueueDTO): "ok" | "warn" | "fail" {
  if (q.paused) return "warn";
  if (q.depth > 0 && q.depth < 50) return "warn";
  if (q.depth >= 50) return "fail";
  return "ok";
}

export function levelVariant(level: string): "ok" | "warn" | "fail" | "neutral" {
  if (level === "error") return "fail";
  if (level === "warn") return "warn";
  if (level === "info") return "ok";
  return "neutral";
}

export function shortJson(x: unknown, max = 800): string {
  try {
    const s = JSON.stringify(x, null, 2);
    return s.length > max ? s.slice(0, max) + "\n…truncated" : s;
  } catch {
    return String(x);
  }
}
