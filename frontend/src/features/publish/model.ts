// frontend/src/features/publish/model.ts
import type {
  PublishTask,
  PublishTasksList,
  PublishStatus,
  PublishProgress,
  PublishRef,
  PublishChannel,
  RunPublishRequest,
} from "./schemas";

export type {
  PublishTask,
  PublishTasksList,
  PublishStatus,
  PublishProgress,
  PublishRef,
  PublishChannel,
  RunPublishRequest,
};

export const PUBLISH_STATUS_LABELS: Record<PublishStatus, string> = {
  queued: "Queued",
  processing: "Processing",
  ready: "Published",
  failed: "Failed",
  canceled: "Canceled",
};

export function publishStatusVariant(
  s: PublishStatus
): "ok" | "warn" | "fail" | "neutral" {
  if (s === "ready") return "ok";
  if (s === "queued" || s === "processing") return "warn";
  if (s === "failed") return "fail";
  return "neutral";
}

export type PublishAction =
  | "start"
  | "retry"
  | "cancel"
  | "unpublish"
  | "open_card";

export function allowedPublishActions(status: PublishStatus): PublishAction[] {
  switch (status) {
    case "queued":
    case "processing":
      return ["cancel"];
    case "failed":
    case "canceled":
      return ["retry"];
    case "ready":
      return ["open_card", "unpublish"]; // unpublish optional; UI hides if backend errors
    default:
      return [];
  }
}

export function progressPercent(p?: PublishProgress) {
  if (!p || p.total === 0) return 0;
  return Math.min(100, Math.round((p.done / p.total) * 100));
}

export function shortJson(x: unknown, max = 600): string {
  try {
    const s = JSON.stringify(x, null, 2);
    return s.length > max ? s.slice(0, max) + "\n…truncated" : s;
  } catch {
    return String(x);
  }
}
