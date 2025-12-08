// cabinet/frontend/src/shared/hooks/useApi.ts
import { useMemo } from "react";
import { http, HttpClient } from "../api/http";

/**
 * Хук возвращает singleton http.
 * Если app подменит http.getToken — всё features получат токен.
 */
export function useHttp(): HttpClient {
  return useMemo(() => http, []);
}
