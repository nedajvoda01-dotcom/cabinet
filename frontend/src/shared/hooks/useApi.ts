import { useMemo } from "react";
import { http, HttpClient } from "../api/http";
import { getAccessToken } from "../session/tokens";

export function useApi(): HttpClient {
  return useMemo(() => {
    http.getToken = getAccessToken;
    return http;
  }, []);
}
