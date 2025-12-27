import React from "react";
import { CardsPage, ParserPage, PhotosPage, ExportPage, PublishPage } from "../../../features/pipeline";
import { Guard } from "../../../shared/rbac/Guard";
import type { Permission } from "../../../shared/rbac/permissions";
import { useAuth } from "../../../features/auth/ui/AuthProvider";

const wrap = (permission: Permission, node: React.ReactNode) => () => {
  const { user } = useAuth();
  return <Guard role={user?.role} permission={permission} fallback={<div className="p-4">Forbidden</div>}>{node}</Guard>;
};

export const workRoutes = [
  { path: "/work", element: wrap("pipeline.view", <CardsPage />) },
  { path: "/work/parser", element: wrap("pipeline.view", <ParserPage />) },
  { path: "/work/photos", element: wrap("pipeline.view", <PhotosPage />) },
  { path: "/work/export", element: wrap("pipeline.view", <ExportPage />) },
  { path: "/work/publish", element: wrap("pipeline.view", <PublishPage />) },
];
