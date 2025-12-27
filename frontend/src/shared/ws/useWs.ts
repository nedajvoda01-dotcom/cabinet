import { useEffect, useRef } from "react";
import { WsClient } from "./client";

export function useWs(url: string, onMessage: (ev: MessageEvent) => void) {
  const clientRef = useRef<WsClient | null>(null);

  useEffect(() => {
    const client = new WsClient(url);
    client.connect(onMessage);
    clientRef.current = client;

    return () => client.close();
  }, [url, onMessage]);
}
