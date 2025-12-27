import React from "react";

export function EmptyState({ title }: { title: string }) {
  return <div className="text-sm text-slate-500 border border-dashed rounded-lg p-4">{title}</div>;
}
