// cabinet/frontend/src/shared/hooks/useWs.ts
import { useEffect } from "react";
import type { WsEventName, WsEnvelope } from "../ws/events";
import { wsClient } from "../ws/wsClient";

/**
 * Подписка на WS событие.
 * Автоподключение при первом использовании.
 */
export function useWsEvent<T = any>(
  event: WsEventName,
  handler: (data: T, env: WsEnvelope<T>) => void
) {
  useEffect(() => {
    if (!wsClient.isConnected()) wsClient.connect();
    return wsClient.on<T>(event, handler);
  }, [event, handler]);
}
