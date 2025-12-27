export type WsListener = (event: MessageEvent) => void;

export class WsClient {
  private socket: WebSocket | null = null;

  constructor(private url: string) {}

  connect(onMessage: WsListener) {
    this.socket = new WebSocket(this.url);
    this.socket.addEventListener("message", onMessage);
  }

  close() {
    this.socket?.close();
  }
}
