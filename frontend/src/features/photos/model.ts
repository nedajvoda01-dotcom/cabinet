// frontend/src/features/photos/model.ts
import type {
  PhotosTask,
  PhotosTasksList,
  PhotosStatus,
  PhotosProgress,
  PhotoArtifact,
  PhotosListForCard,
  RunPhotosRequest,
} from "./schemas";

export type {
  PhotosTask,
  PhotosTasksList,
  PhotosStatus,
  PhotosProgress,
  PhotoArtifact,
  PhotosListForCard,
  RunPhotosRequest,
};

export const PHOTOS_STATUS_LABELS: Record<PhotosStatus, string> = {
  queued: "Queued",
  processing: "Processing",
  ready: "Ready",
  failed: "Failed",
  canceled: "Canceled",
};

export function photosStatusVariant(
  s: PhotosStatus
): "ok" | "warn" | "fail" | "neutral" {
  if (s === "ready") return "ok";
  if (s === "queued" || s === "processing") return "warn";
  if (s === "failed") return "fail";
  return "neutral";
}

export type PhotosAction = "start" | "retry" | "cancel" | "open_card";

export function allowedPhotosActions(status: PhotosStatus): PhotosAction[] {
  switch (status) {
    case "queued":
    case "processing":
      return ["cancel"];
    case "failed":
    case "canceled":
      return ["retry"];
    case "ready":
      return ["open_card"];
    default:
      return [];
  }
}

export function progressPercent(p?: PhotosProgress) {
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
