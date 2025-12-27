// cabinet/frontend/src/apps/operator/routes.tsx
import React from "react";
import { Routes, Route, Navigate } from "react-router-dom";

import { CardsListPage, CardDetailsPage } from "../../features/cards/ui";
import { ParserTasksListPage, ParserTaskDetailsPage } from "../../features/parser/ui";
import { PhotosTasksListPage, PhotosTaskDetailsPage } from "../../features/photos/ui";
import { ExportListPage, ExportDetailsPage } from "../../features/export/ui";
import { PublishTasksListPage, PublishTaskDetailsPage } from "../../features/publish/ui";

/**
 * Operator routes = основной рабочий контур.
 * Все страницы защищены RequireAuth на уровне AppShell.
 */
export function OperatorRoutes() {
  return (
    <Routes>
      <Route path="/" element={<Navigate to="/operator/cards" replace />} />

      {/* Cards */}
      <Route path="/cards" element={<CardsListPage />} />
      <Route path="/cards/:id" element={<CardRouteWrapper />} />

      {/* Pipeline tasks */}
      <Route path="/parser" element={<ParserTasksListPage />} />
      <Route path="/parser/tasks/:id" element={<ParserTaskRouteWrapper />} />

      <Route path="/photos" element={<PhotosTasksListPage />} />
      <Route path="/photos/tasks/:id" element={<PhotosTaskRouteWrapper />} />

      <Route path="/export" element={<ExportListPage />} />
      <Route path="/export/:id" element={<ExportRouteWrapper />} />

      <Route path="/publish" element={<PublishTasksListPage />} />
      <Route path="/publish/tasks/:id" element={<PublishTaskRouteWrapper />} />

      <Route path="*" element={<NotFound />} />
    </Routes>
  );
}

// --------- small wrappers to read :id param ---------
import { useParams } from "react-router-dom";

function CardRouteWrapper() {
  const { id } = useParams();
  return <CardDetailsPage cardId={id!} />;
}

function ParserTaskRouteWrapper() {
  const { id } = useParams();
  return <ParserTaskDetailsPage taskId={id!} />;
}

function PhotosTaskRouteWrapper() {
  const { id } = useParams();
  return <PhotosTaskDetailsPage taskId={id!} />;
}

function ExportRouteWrapper() {
  const { id } = useParams();
  return <ExportDetailsPage exportId={id!} />;
}

function PublishTaskRouteWrapper() {
  const { id } = useParams();
  return <PublishTaskDetailsPage taskId={id!} />;
}

function NotFound() {
  return <div className="p-4 text-sm">Operator page not found</div>;
}
