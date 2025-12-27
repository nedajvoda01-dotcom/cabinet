// cabinet/frontend/src/index.tsx
import React from "react";
import { createRoot } from "react-dom/client";
import AppShell from "./AppShell";

// optional: global styles import if exists
// import "./index.css";

const el = document.getElementById("root");
if (!el) throw new Error("Root element #root not found");

createRoot(el).render(
  <React.StrictMode>
    <AppShell />
  </React.StrictMode>
);
