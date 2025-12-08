// frontend/src/features/export/model.ts
import type {
  ExportTask,
  ExportList,
  ExportStatus,
  ExportFormat,
} from "./schemas";

export type {
  ExportTask,
  ExportList,
  ExportStatus,
  ExportFormat,
};

export const EXPORT_STATUS_LABELS: Record<ExportStatus, string> = {
  queued: "Queued",
  processing: "Processing",
  ready: "Ready",
  failed: "Failed",
  canceled: "Canceled",
};

export function exportStatusVariant(
  s: ExportStatus
): "ok" | "warn" | "fail" | "neutral" {
  if (s === "ready") return "ok";
  if (s === "queued" || s === "processing") return "warn";
  if (s === "failed") return "fail";
  return "neutral";
}

export type ExportAction = "start" | "retry" | "cancel" | "download";

export function allowedExportActions(status: ExportStatus): ExportAction[] {
  switch (status) {
    case "queued":
    case "processing":
      return ["cancel"];
    case "failed":
    case "canceled":
      return ["retry"];
    case "ready":
      return ["download"];
    default:
      return [];
  }
}

export function formatFormat(f?: ExportFormat) {
  return (f ?? "csv").toUpperCase();
}

export function formatBytes(n?: number) {
  if (n == null) return "—";
  if (n < 1024) return `${n} B`;
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`;
  return `${(n / (1024 * 1024)).toFixed(1)} MB`;
}
