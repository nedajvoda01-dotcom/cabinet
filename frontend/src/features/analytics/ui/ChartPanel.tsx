import React from "react";

export function ChartPanel({ title }: { title: string }) {
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-3">
      <div className="text-sm font-semibold">{title}</div>
      <div className="text-xs text-slate-500">Chart placeholder</div>
    </div>
  );
}
