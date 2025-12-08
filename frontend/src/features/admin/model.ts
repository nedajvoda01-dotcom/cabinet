// cabinet/frontend/src/features/admin/model.ts
import type {
  DashboardKpi,
  QueueKpi,
  DlqJob,
  LogItem,
  Health,
} from "./schemas";

export type {
  DashboardKpi,
  QueueKpi,
  DlqJob,
  LogItem,
  Health,
};

export type QueueType =
  | "photos"
  | "export"
  | "publish"
  | "parser"
  | "status"
  | string;

export type IntegrationStatus = "ok" | "fail" | "degraded" | "unknown";

export const QUEUE_LABELS: Record<string, string> = {
  photos: "Photos queue",
  export: "Export queue",
  publish: "Publish queue",
  parser: "Parser intake queue",
  status: "Status updates queue",
};

export function formatLatency(ms?: number) {
  if (ms == null) return "—";
  if (ms < 1000) return `${ms} ms`;
  return `${(ms / 1000).toFixed(2)} s`;
}

export function formatRate(rate?: number) {
  if (rate == null) return "—";
  return `${rate.toFixed(1)}/min`;
}

export function statusBadgeVariant(status: IntegrationStatus) {
  switch (status) {
    case "ok": return "ok";
    case "degraded": return "warn";
    case "fail": return "fail";
    default: return "neutral";
  }
}
