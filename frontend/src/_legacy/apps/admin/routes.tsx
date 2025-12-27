// cabinet/frontend/src/apps/admin/routes.tsx
import React from "react";
import { Routes, Route, Navigate } from "react-router-dom";

import { CardsListPage, CardDetailsPage } from "../../features/cards/ui";
import { ParserTasksListPage, ParserTaskDetailsPage } from "../../features/parser/ui";
import { PhotosTasksListPage, PhotosTaskDetailsPage } from "../../features/photos/ui";
import { ExportListPage, ExportDetailsPage } from "../../features/export/ui";
import { PublishTasksListPage, PublishTaskDetailsPage } from "../../features/publish/ui";

export function AdminRoutes() {
  return (
    <Routes>
      <Route path="/" element={<Navigate to="/admin/cards" replace />} />

      <Route path="/cards" element={<CardsListPage />} />
      <Route path="/cards/:id" element={<CardRouteWrapper />} />

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
  return <div className="p-4 text-sm">Admin page not found</div>;
}
