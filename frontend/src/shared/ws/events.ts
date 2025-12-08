// cabinet/frontend/src/shared/ws/events.ts

/** Имена событий по Spec */
export type WsEventName =
  | "card.status.updated"
  | "photos.progress"
  | "export.progress"
  | "publish.progress"
  | "publish.status.updated"
  | "queue.depth.updated"
  | "dlq.updated"
  | "health.updated"
  | string;

export type WsEnvelope<T = any> = {
  event: WsEventName;
  data: T;
  ts?: string;              // ISO
  correlation_id?: string;
};
