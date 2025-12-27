import React from "react";
import { analyticsApi } from "../api";
import { ChartPanel } from "./ChartPanel";

export function AnalyticsPage() {
  const [charts, setCharts] = React.useState<{ id: string; title: string }[]>([]);

  React.useEffect(() => {
    analyticsApi.summary().then((res) => setCharts(res.charts));
  }, []);

  return (
    <div className="p-4 space-y-3">
      <h1 className="text-xl font-semibold">Analytics</h1>
      {charts.map((c) => (
        <ChartPanel key={c.id} title={c.title} />
      ))}
      {charts.length === 0 && <div className="text-sm text-slate-500">No data yet</div>}
    </div>
  );
}
