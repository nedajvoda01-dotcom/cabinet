// cabinet/frontend/src/shared/ws/wsClient.ts
import type { WsEnvelope, WsEventName } from "./events";

type Handler<T = any> = (data: T, envelope: WsEnvelope<T>) => void;

export interface WsClientOptions {
  url?: string;                     // default: ws(s)://<host>/ws
  reconnect?: boolean;              // default true
  reconnectDelayMs?: number;        // default 1500
  getToken?: () => string | null;   // if ws uses token query
}

/**
 * Минимальный WS клиент с подписками.
 */
export class WsClient {
  private url: string;
  private reconnect: boolean;
  private reconnectDelayMs: number;
  private getToken?: () => string | null;

  private ws: WebSocket | null = null;
  private handlers: Map<WsEventName, Set<Handler>> = new Map();
  private anyHandlers: Set<Handler> = new Set();
  private closedByUser = false;

  constructor(opts: WsClientOptions = {}) {
    const defaultUrl = (() => {
      if (typeof window === "undefined") return "ws://localhost/ws";
      const proto = window.location.protocol === "https:" ? "wss" : "ws";
      return `${proto}://${window.location.host}/ws`;
    })();

    this.url = opts.url ?? defaultUrl;
    this.reconnect = opts.reconnect ?? true;
    this.reconnectDelayMs = opts.reconnectDelayMs ?? 1500;
    this.getToken = opts.getToken;
  }

  connect() {
    this.closedByUser = false;

    const token = this.getToken?.();
    const urlWithToken = token
      ? `${this.url}${this.url.includes("?") ? "&" : "?"}token=${encodeURIComponent(token)}`
      : this.url;

    this.ws = new WebSocket(urlWithToken);

    this.ws.onmessage = (e) => {
      let env: WsEnvelope;
      try {
        env = JSON.parse(e.data);
      } catch {
        return;
      }
      const { event, data } = env;

      this.anyHandlers.forEach((h) => h(data, env));
      const set = this.handlers.get(event);
      if (set) set.forEach((h) => h(data, env));
    };

    this.ws.onclose = () => {
      this.ws = null;
      if (!this.closedByUser && this.reconnect) {
        setTimeout(() => this.connect(), this.reconnectDelayMs);
      }
    };

    this.ws.onerror = () => {
      // даст onclose -> reconnect
      try { this.ws?.close(); } catch {}
    };
  }

  close() {
    this.closedByUser = true;
    try { this.ws?.close(); } catch {}
    this.ws = null;
  }

  on<T = any>(event: WsEventName, handler: Handler<T>) {
    if (!this.handlers.has(event)) this.handlers.set(event, new Set());
    this.handlers.get(event)!.add(handler as Handler);
    return () => this.off(event, handler);
  }

  onAny<T = any>(handler: Handler<T>) {
    this.anyHandlers.add(handler as Handler);
    return () => this.offAny(handler);
  }

  off<T = any>(event: WsEventName, handler: Handler<T>) {
    this.handlers.get(event)?.delete(handler as Handler);
  }

  offAny<T = any>(handler: Handler<T>) {
    this.anyHandlers.delete(handler as Handler);
  }

  send(event: string, data: any) {
    if (!this.ws || this.ws.readyState !== WebSocket.OPEN) return;
    this.ws.send(JSON.stringify({ event, data }));
  }

  isConnected() {
    return this.ws?.readyState === WebSocket.OPEN;
  }
}

export const wsClient = new WsClient();
