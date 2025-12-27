import React from "react";
import { Outlet } from "react-router-dom";
import { Sidebar } from "./Sidebar";
import { Topbar } from "./Topbar";
import { AppContent } from "./AppContent";
import { AppContentRoutes } from "../index";

export function AppShell() {
  return (
    <div className="min-h-screen bg-slate-50">
      <Topbar />
      <div className="flex">
        <Sidebar />
        <AppContent>
          <AppContentRoutes />
          <Outlet />
        </AppContent>
      </div>
    </div>
  );
}
