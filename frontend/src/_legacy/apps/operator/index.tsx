// cabinet/frontend/src/apps/operator/index.tsx
import React from "react";
import { OperatorRoutes } from "./routes";

/**
 * Тонкая обёртка — все экраны внутри OperatorRoutes.
 */
export default function OperatorApp() {
  return <OperatorRoutes />;
}
