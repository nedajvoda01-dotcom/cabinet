// cabinet/frontend/src/features/admin/ui/index.tsx
import React, { useEffect, useMemo, useState } from "react";
import { adminApi } from "../api";
import type { QueueDTO, DlqItem, JobDTO, LogEntry, IntegrationHealth } from "../schemas";
import { queueVariant, levelVariant, shortJson } from "../model";
import { useWsEvent } from "../../shared/hooks/useWs";

// ---------- atoms ----------
function Box(props: { title?: string; children: React.ReactNode; className?: string }) {
  return (
    <div className={`rounded-2xl bg-white shadow p-4 border border-gray-200 ${props.className ?? ""}`}>
      {props.title && <div className="font-semibold mb-2">{props.title}</div>}
      {props.children}
    </div>
  );
}
function Badge(props: { variant: "ok" | "warn" | "fail" | "neutral"; children: React.ReactNode }) {
  const cls =
    props.variant === "ok" ? "bg-emerald-100 text-emerald-800" :
    props.variant === "warn" ? "bg-amber-100 text-amber-800" :
    props.variant === "fail" ? "bg-red-100 text-red-800" :
    "bg-gray-100 text-gray-800";
  return <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${cls}`}>{props.children}</span>;
}
function Button(props: React.ButtonHTMLAttributes<HTMLButtonElement>) {
  return (
    <button
      {...props}
      className={`px-3 py-1.5 rounded-xl text-sm border hover:bg-gray-50 disabled:opacity-50 ${props.className ?? ""}`}
    />
  );
}

// --------------------------------------------------
// 1) Queues dashboard page
// --------------------------------------------------
export function AdminQueuesPage() {
  const [queues, setQueues] = useState<QueueDTO[]>([]);
  const [selected, setSelected] = useState<QueueDTO | null>(null);
  const [jobs, setJobs] = useState<JobDTO[]>([]);
  const [err, setErr] = useState<string | null>(null);

  const loadQueues = () =>
    adminApi.listQueues()
      .then((r) => (setQueues(r.items), setErr(null)))
      .catch((e) => setErr(e.message));

  const loadJobs = (q: QueueDTO) =>
    adminApi.listQueueJobs(String(q.type), { limit: 50 })
      .then((r) => setJobs(r.items))
      .catch((e) => setErr(e.message));

  useEffect(() => { loadQueues(); }, []);

  // WS live depth updates
  useWsEvent("queue.depth.updated", (data: any) => {
    // ожидаем { type, depth, paused? }
    setQueues((prev) =>
      prev.map((q) =>
        String(q.type) === String(data.type)
          ? { ...q, depth: data.depth ?? q.depth, paused: data.paused ?? q.paused }
          : q
      )
    );
  });

  const onSelect = (q: QueueDTO) => {
    setSelected(q);
    loadJobs(q);
  };

  const onPauseResume = async (q: QueueDTO) => {
    try {
      if (q.paused) await adminApi.resumeQueue(String(q.type));
      else await adminApi.pauseQueue(String(q.type));
      await loadQueues();
      if (selected && selected.type === q.type) {
        onSelect({ ...q, paused: !q.paused });
      }
    } catch (e: any) {
      setErr(e.message);
    }
  };

  return (
    <div className="p-4 space-y-4">
      {err && <div className="text-sm text-red-600">Error: {err}</div>}

      <Box title="Queues">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
          {queues.map((q) => (
            <div
              key={String(q.type)}
              className={`rounded-xl border p-3 cursor-pointer hover:bg-gray-50 ${
                selected?.type === q.type ? "border-black" : "border-gray-200"
              }`}
              onClick={() => onSelect(q)}
            >
              <div className="flex items-center justify-between">
                <div className="font-semibold">{q.type}</div>
                <Badge variant={queueVariant(q)}>
                  {q.paused ? "paused" : `depth ${q.depth}`}
                </Badge>
              </div>

              <div className="text-xs text-gray-500 mt-1">
                {q.in_flight != null && <div>in_flight: {q.in_flight}</div>}
                {q.retries_24h != null && <div>retries/24h: {q.retries_24h}</div>}
                {q.dlq_24h != null && <div>dlq/24h: {q.dlq_24h}</div>}
              </div>

              <div className="mt-2">
                <Button onClick={(e) => (e.stopPropagation(), onPauseResume(q))}>
                  {q.paused ? "Resume" : "Pause"}
                </Button>
              </div>
            </div>
          ))}
        </div>
      </Box>

      {selected && (
        <Box title={`Jobs in ${selected.type}`}>
          <div className="space-y-2">
            {jobs.map((j) => (
              <div key={String(j.job_id)} className="border rounded-lg p-2 text-sm">
                <div className="flex items-center gap-2">
                  <div className="font-mono">#{j.job_id}</div>
                  <Badge variant={j.status === "failed" || j.status === "dlq" ? "fail" : "neutral"}>
                    {j.status ?? "unknown"}
                  </Badge>
                  {j.card_id != null && <div className="text-xs text-gray-500">card {j.card_id}</div>}
                  <div className="ml-auto text-xs text-gray-500">attempts {j.attempts}</div>
                </div>
                {j.last_error?.message && (
                  <div className="text-xs text-red-600 mt-1">
                    {j.last_error.code && <span className="font-mono mr-1">{j.last_error.code}</span>}
                    {j.last_error.message}
                  </div>
                )}
                {j.payload_json && (
                  <pre className="text-xs bg-gray-50 p-2 rounded mt-2 overflow-auto">
                    {shortJson(j.payload_json)}
                  </pre>
                )}
              </div>
            ))}
            {jobs.length === 0 && <div className="text-sm text-gray-500">No jobs</div>}
          </div>
        </Box>
      )}
    </div>
  );
}

// --------------------------------------------------
// 2) DLQ page
// --------------------------------------------------
export function AdminDlqPage() {
  const [items, setItems] = useState<DlqItem[]>([]);
  const [selected, setSelected] = useState<DlqItem | null>(null);
  const [checked, setChecked] = useState<Record<string, boolean>>({});
  const [err, setErr] = useState<string | null>(null);

  const load = () =>
    adminApi.listDlq({ limit: 100 })
      .then((r) => (setItems(r.items), setErr(null)))
      .catch((e) => setErr(e.message));

  useEffect(() => { load(); }, []);

  useWsEvent("dlq.updated", () => load());

  const retryOne = async (id: string | number) => {
    try {
      await adminApi.retryDlq(id, {});
      load();
    } catch (e: any) {
      setErr(e.message);
    }
  };

  const bulkRetry = async () => {
    const ids = items.filter((x) => checked[String(x.id)]).map((x) => x.id);
    if (!ids.length) return;
    try {
      await adminApi.bulkRetryDlq({ ids });
      setChecked({});
      load();
    } catch (e: any) {
      setErr(e.message);
    }
  };

  return (
    <div className="p-4 space-y-4">
      {err && <div className="text-sm text-red-600">Error: {err}</div>}

      <Box title="DLQ items">
        <div className="flex justify-end mb-2">
          <Button onClick={bulkRetry}>Bulk retry selected</Button>
        </div>

        <div className="space-y-2">
          {items.map((d) => (
            <div
              key={String(d.id)}
              className={`border rounded-lg p-2 text-sm cursor-pointer ${
                selected?.id === d.id ? "border-black" : "border-gray-200"
              }`}
              onClick={() => setSelected(d)}
            >
              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={!!checked[String(d.id)]}
                  onChange={(e) =>
                    setChecked((c) => ({ ...c, [String(d.id)]: e.target.checked }))
                  }
                  onClick={(e) => e.stopPropagation()}
                />
                <div className="font-mono">DLQ #{d.id}</div>
                <div className="text-xs text-gray-500">type {d.type}</div>
                <div className="ml-auto text-xs text-gray-500">attempts {d.attempts}</div>
                <Button onClick={(e) => (e.stopPropagation(), retryOne(d.id))}>Retry</Button>
              </div>
              {d.last_error?.message && (
                <div className="text-xs text-red-600 mt-1">
                  {d.last_error.code && <span className="font-mono mr-1">{d.last_error.code}</span>}
                  {d.last_error.message}
                </div>
              )}
            </div>
          ))}
          {items.length === 0 && <div className="text-sm text-gray-500">DLQ empty</div>}
        </div>
      </Box>

      {selected && (
        <Box title={`DLQ #${selected.id} details`}>
          <div className="text-sm">
            <div><b>Job:</b> {String(selected.job_id)} ({selected.type})</div>
            <div><b>Attempts:</b> {selected.attempts}</div>
            <div><b>Created:</b> {selected.created_at}</div>
          </div>

          {selected.payload_json && (
            <pre className="text-xs bg-gray-50 p-2 rounded mt-2 overflow-auto">
              {JSON.stringify(selected.payload_json, null, 2)}
            </pre>
          )}
        </Box>
      )}
    </div>
  );
}

// --------------------------------------------------
// 3) Logs page
// --------------------------------------------------
export function AdminLogsPage() {
  const [items, setItems] = useState<LogEntry[]>([]);
  const [err, setErr] = useState<string | null>(null);

  const load = () =>
    adminApi.listLogs({ limit: 200 })
      .then((r) => (setItems(r.items), setErr(null)))
      .catch((e) => setErr(e.message));

  useEffect(() => { load(); }, []);

  return (
    <div className="p-4 space-y-4">
      {err && <div className="text-sm text-red-600">Error: {err}</div>}
      <Box title="Latest logs">
        <div className="space-y-2">
          {items.map((l, i) => (
            <div key={i} className="border rounded-lg p-2 text-sm">
              <div className="flex items-center gap-2">
                <Badge variant={levelVariant(String(l.level))}>{String(l.level)}</Badge>
                <div className="font-medium">{l.message}</div>
                <div className="ml-auto text-xs text-gray-500">{l.created_at}</div>
              </div>
              <div className="text-xs text-gray-500 mt-1 flex flex-wrap gap-2">
                {l.correlation_id && <span>corr: <b>{l.correlation_id}</b></span>}
                {l.type && <span>type: {l.type}</span>}
                {l.card_id != null && <span>card: {l.card_id}</span>}
              </div>
              {l.context && (
                <pre className="text-xs bg-gray-50 p-2 rounded mt-2 overflow-auto">
                  {shortJson(l.context)}
                </pre>
              )}
            </div>
          ))}
          {items.length === 0 && <div className="text-sm text-gray-500">No logs</div>}
        </div>
      </Box>
    </div>
  );
}

// --------------------------------------------------
// 4) Integrations / Health page
// --------------------------------------------------
export function AdminIntegrationsPage() {
  const [items, setItems] = useState<IntegrationHealth[]>([]);
  const [ok, setOk] = useState<boolean>(true);
  const [kpi, setKpi] = useState<any>(null);
  const [err, setErr] = useState<string | null>(null);

  const load = () =>
    adminApi.getHealth()
      .then((r) => {
        setItems(r.integrations ?? []);
        setOk(!!r.ok);
        setKpi(r.kpi ?? null);
        setErr(null);
      })
      .catch((e) => setErr(e.message));

  useEffect(() => { load(); }, []);

  useWsEvent("health.updated", () => load());

  return (
    <div className="p-4 space-y-4">
      {err && <div className="text-sm text-red-600">Error: {err}</div>}

      <Box title="System health">
        <div className="flex items-center gap-2">
          <Badge variant={ok ? "ok" : "fail"}>{ok ? "OK" : "DEGRADED"}</Badge>
          <div className="text-sm text-gray-600">Integrations summary</div>
        </div>

        {kpi && (
          <pre className="text-xs bg-gray-50 p-2 rounded mt-2 overflow-auto">
            {shortJson(kpi)}
          </pre>
        )}
      </Box>

      <Box title="Integrations">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
          {items.map((it) => (
            <div key={it.name} className="border rounded-xl p-3">
              <div className="flex items-center justify-between">
                <div className="font-semibold">{it.name}</div>
                <Badge variant={it.ok ? "ok" : "fail"}>{it.ok ? "ok" : "fail"}</Badge>
              </div>
              <div className="text-xs text-gray-500 mt-1">
                {it.latency_ms != null && <div>latency: {it.latency_ms} ms</div>}
                {it.updated_at && <div>updated: {it.updated_at}</div>}
              </div>
              {it.last_error?.message && (
                <div className="text-xs text-red-600 mt-1">
                  {it.last_error.code && <span className="font-mono mr-1">{it.last_error.code}</span>}
                  {it.last_error.message}
                </div>
              )}
              {it.meta && (
                <pre className="text-xs bg-gray-50 p-2 rounded mt-2 overflow-auto">
                  {shortJson(it.meta)}
                </pre>
              )}
            </div>
          ))}
          {items.length === 0 && <div className="text-sm text-gray-500">No integrations</div>}
        </div>
      </Box>
    </div>
  );
}
