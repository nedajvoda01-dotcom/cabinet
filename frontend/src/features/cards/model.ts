// cabinet/frontend/src/features/cards/model.ts
import type { Card, CardStatus, CardsList, CardPhoto } from "./schemas";

export type { Card, CardStatus, CardsList, CardPhoto };

// Human labels
export const STATUS_LABELS: Record<CardStatus, string> = {
  draft: "Draft",

  photos_queued: "Photos queued",
  photos_processing: "Photos processing",
  photos_ready: "Photos ready",
  photos_failed: "Photos failed",

  ready_for_export: "Ready for export",
  export_queued: "Export queued",
  export_processing: "Export processing",
  export_ready: "Export ready",
  export_failed: "Export failed",

  ready_for_publish: "Ready for publish",
  publish_queued: "Publish queued",
  publish_processing: "Publish processing",
  published: "Published",
  publish_failed: "Publish failed",

  blocked: "Blocked",
};

// Allowed UI actions per StateMachine
export type CardAction =
  | "start_photos"
  | "retry_photos"
  | "start_export"
  | "retry_export"
  | "start_publish"
  | "retry_publish";

export function allowedActions(status: CardStatus): CardAction[] {
  switch (status) {
    case "draft":
      return ["start_photos"];
    case "photos_failed":
      return ["retry_photos"];
    case "photos_ready":
      return ["start_export"];
    case "export_failed":
      return ["retry_export"];
    case "ready_for_publish":
      return ["start_publish"];
    case "publish_failed":
      return ["retry_publish"];
    default:
      return [];
  }
}

export function statusVariant(status: CardStatus): "ok" | "warn" | "fail" | "neutral" {
  if (status.endsWith("_failed") || status === "blocked") return "fail";
  if (status.endsWith("_processing") || status.endsWith("_queued")) return "warn";
  if (status === "photos_ready" || status === "export_ready" || status === "published") return "ok";
  return "neutral";
}

export function formatVehicle(v: Card["vehicle"]) {
  const bits = [v.make, v.model, v.year].filter(Boolean);
  return bits.join(" ");
}

export function formatPrice(p?: Card["price"]) {
  if (!p) return "—";
  return `${p.value.toLocaleString()} ${p.currency}`;
}
