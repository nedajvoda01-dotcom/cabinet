// frontend/src/features/parser/model.ts
import type {
  ParserTask,
  ParserTasksList,
  ParserStatus,
  RunParserRequest,
} from "./schemas";

export type {
  ParserTask,
  ParserTasksList,
  ParserStatus,
  RunParserRequest,
};

export const PARSER_STATUS_LABELS: Record<ParserStatus, string> = {
  queued: "Queued",
  processing: "Processing",
  ready: "Ready",
  failed: "Failed",
  canceled: "Canceled",
};

export function parserStatusVariant(
  s: ParserStatus
): "ok" | "warn" | "fail" | "neutral" {
  if (s === "ready") return "ok";
  if (s === "queued" || s === "processing") return "warn";
  if (s === "failed") return "fail";
  return "neutral";
}

export type ParserAction = "start" | "retry" | "cancel" | "open_card";

export function allowedParserActions(status: ParserStatus): ParserAction[] {
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

export function shortJson(x: unknown, max = 600): string {
  try {
    const s = JSON.stringify(x, null, 2);
    return s.length > max ? s.slice(0, max) + "\n…truncated" : s;
  } catch {
    return String(x);
  }
}
