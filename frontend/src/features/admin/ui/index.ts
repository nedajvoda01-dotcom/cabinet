// cabinet/frontend/src/features/admin/ui/index.tsx
import React, { useEffect, useMemo, useState } from "react";
import { adminApi } from "../api";
import type {
  DashboardKpi,
  QueueKpi,
  DlqJob,
  LogsList,
  Health,
} from "../schemas";
import { formatLatency, formatRate, QUEUE_LABELS } from "../model";

// -----------------------------
// shared bits
// -----------------------------
function Card(props: { title: string; children: React.ReactNode; className?: string }) {
  return (
    <div className={`rounded-2xl bg-white shadow p-4 border border-gray-200 ${props.className ?? ""}`}>
      <div className="font-semibold mb-2">{props.title}</div>
      {props.children}
    </div>
  );
}

function Section(props: { title: string; children: React.ReactNode }) {
  return (
    <div className="space-y-3">
      <h2 className="text-xl font-bold">{props.title}</h2>
      {props.children}
    </div>
  );
}

function Badge(props: { variant?: "ok" | "fail" | "warn" | "neutral"; children: React.ReactNode }) {
  const v = props.variant ?? "neutral";
  const cls =
    v === "ok" ? "bg-emerald-100 text-emerald-800" :
    v === "fail" ? "bg-red-100 text-red-800" :
    v === "warn" ? "bg-amber-100 text-amber-800" :
    "bg-gray-100 text-gray-800";
  return <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${cls}`}>{props.children}</span>;
}

function ErrorBox(props: { code?: string; message?: string }) {
  if (!props.code && !props.message) return null;
  return (
    <div className="mt-2 rounded-xl bg-red-50 border border-red-200 p-2 text-sm">
      <div className="font-mono text-xs text-red-700">{props.code}</div>
      <div className="text-red-900">{props.message}</div>
    </div>
  );
}

// -----------------------------
// Admin Dashboard Page
// -----------------------------
export function AdminDashboardPage() {
  const [data, setData] = useState<DashboardKpi | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let alive = true;
    setLoading(true);
    adminApi.getDashboard()
      .then((d) => alive && (setData(d), setErr(null)))
      .catch((e) => alive && setErr(e.message))
      .finally(() => alive && setLoading(false));
    return () => { alive = false; };
  }, []);

  if (loading) return <div className="p-4">Loading dashboard…</div>;
  if (err) return <div className="p-4 text-red-600">Error: {err}</div>;
  if (!data) return null;

  return (
    <div className="p-4 space-y-6">
      <Section title="Admin Dashboard">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
          <Card title="Throughput">
            <div className="text-2xl font-bold">{data.throughput_per_min.toFixed(1)}/min</div>
          </Card>
          <Card title="Retries %">
            <div className="text-2xl font-bold">{data.retries_percent.toFixed(1)}%</div>
          </Card>
          <Card title="DLQ growth 24h">
            <div className="text-2xl font-bold">{data.dlq_growth_24h > 0 ? "+" : ""}{data.dlq_growth_24h}</div>
          </Card>
        </div>
      </Section>

      <Section title="Queues">
        <QueuesTable queues={data.queues} />
      </Section>

      <Section title="Integrations health">
        <IntegrationsTable integrations={data.integrations} />
      </Section>

      <Section title="Top errors 24h">
        <Card title="Errors">
          {data.errors_top.length === 0 ? (
            <div className="text-sm text-gray-500">No errors</div>
          ) : (
            <ul className="space-y-1">
              {data.errors_top.map((e) => (
                <li key={e.code} className="flex items-center justify-between text-sm">
                  <span className="font-mono">{e.code}</span>
                  <span className="text-gray-700">{e.message ?? "—"}</span>
                  <span className="font-semibold">{e.count}</span>
                </li>
              ))}
            </ul>
          )}
        </Card>
      </Section>
    </div>
  );
}

// -----------------------------
// Admin Queues Page
// -----------------------------
export function AdminQueuesPage() {
  const [queues, setQueues] = useState<QueueKpi[]>([]);
  const [err, setErr] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [busyType, setBusyType] = useState<string | null>(null);

  const reload = () => {
    setLoading(true);
    adminApi.getQueues()
      .then((r) => (setQueues(r.items), setErr(null)))
      .catch((e) => setErr(e.message))
      .finally(() => setLoading(false));
  };

  useEffect(() => { reload(); }, []);

  const onPauseResume = async (q: QueueKpi) => {
    try {
      setBusyType(q.type);
      if (q.paused) await adminApi.resumeQueue(q.type);
      else await adminApi.pauseQueue(q.type);
      reload();
    } catch (e: any) {
      setErr(e.message);
    } finally {
      setBusyType(null);
    }
  };

  if (loading) return <div className="p-4">Loading queues…</div>;

  return (
    <div className="p-4 space-y-4">
      <Section title="Queues">
        {err && <div className="text-red-600 text-sm">{err}</div>}
        <QueuesTable
          queues={queues}
          renderActions={(q) => (
            <button
              className="px-3 py-1 rounded-lg border text-sm hover:bg-gray-50 disabled:opacity-50"
              disabled={busyType === q.type}
              onClick={() => onPauseResume(q)}
            >
              {q.paused ? "Resume" : "Pause"}
            </button>
          )}
        />
      </Section>
    </div>
  );
}

function QueuesTable(props: {
  queues: QueueKpi[];
  renderActions?: (q: QueueKpi) => React.ReactNode;
}) {
  return (
    <Card title="Queues list">
      <div className="overflow-auto">
        <table className="min-w-full text-sm">
          <thead>
            <tr className="text-left text-gray-600">
              <th className="py-2 pr-3">Type</th>
              <th className="py-2 pr-3">Depth</th>
              <th className="py-2 pr-3">Retrying</th>
              <th className="py-2 pr-3">Rate</th>
              <th className="py-2 pr-3">Latency</th>
              <th className="py-2 pr-3">State</th>
              {props.renderActions && <th className="py-2 pr-3">Actions</th>}
            </tr>
          </thead>
          <tbody>
            {props.queues.map((q) => (
              <tr key={q.type} className="border-t">
                <td className="py-2 pr-3">
                  <div className="font-medium">{QUEUE_LABELS[q.type] ?? q.type}</div>
                  <div className="text-xs text-gray-500 font-mono">{q.type}</div>
                </td>
                <td className="py-2 pr-3 font-semibold">{q.depth}</td>
                <td className="py-2 pr-3">{q.retrying ?? 0}</td>
                <td className="py-2 pr-3">{formatRate(q.rate_per_min)}</td>
                <td className="py-2 pr-3">{formatLatency(q.avg_latency_ms)}</td>
                <td className="py-2 pr-3">
                  <Badge variant={q.paused ? "warn" : "ok"}>
                    {q.paused ? "paused" : "running"}
                  </Badge>
                </td>
                {props.renderActions && (
                  <td className="py-2 pr-3">{props.renderActions(q)}</td>
                )}
              </tr>
            ))}
            {props.queues.length === 0 && (
              <tr><td className="py-4 text-gray-500" colSpan={7}>No queues</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </Card>
  );
}

// -----------------------------
// Admin DLQ Page
// -----------------------------
export function AdminDlqPage() {
  const [items, setItems] = useState<DlqJob[]>([]);
  const [selected, setSelected] = useState<Set<string | number>>(new Set());
  const [err, setErr] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const reload = () => {
    setLoading(true);
    adminApi.getDlq()
      .then((r) => (setItems(r.items), setErr(null)))
      .catch((e) => setErr(e.message))
      .finally(() => setLoading(false));
  };

  useEffect(() => { reload(); }, []);

  const toggle = (id: string | number) => {
    setSelected((prev) => {
      const n = new Set(prev);
      n.has(id) ? n.delete(id) : n.add(id);
      return n;
    });
  };

  const retryOne = async (id: string | number) => {
    try {
      await adminApi.retryDlq({ dlq_id: id });
      reload();
    } catch (e: any) { setErr(e.message); }
  };

  const dropOne = async (id: string | number) => {
    try {
      await adminApi.dropDlq({ dlq_id: id });
      reload();
    } catch (e: any) { setErr(e.message); }
  };

  const bulkRetry = async () => {
    try {
      await adminApi.retryDlqBulk({ dlq_ids: Array.from(selected) });
      setSelected(new Set());
      reload();
    } catch (e: any) { setErr(e.message); }
  };

  if (loading) return <div className="p-4">Loading DLQ…</div>;

  return (
    <div className="p-4 space-y-4">
      <Section title="Dead Letter Queue">
        {err && <div className="text-red-600 text-sm">{err}</div>}

        <div className="flex gap-2">
          <button
            className="px-3 py-1 rounded-lg border text-sm hover:bg-gray-50 disabled:opacity-50"
            disabled={selected.size === 0}
            onClick={bulkRetry}
          >
            Bulk retry ({selected.size})
          </button>
          <button
            className="px-3 py-1 rounded-lg border text-sm hover:bg-gray-50"
            onClick={reload}
          >
            Refresh
          </button>
        </div>

        <Card title="DLQ items">
          <div className="space-y-2">
            {items.map((j) => {
              const isSel = selected.has(j.id);
              return (
                <div key={String(j.id)} className="border rounded-xl p-3">
                  <div className="flex items-start gap-2">
                    <input
                      type="checkbox"
                      checked={isSel}
                      onChange={() => toggle(j.id)}
                    />
                    <div className="flex-1">
                      <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="fail">{j.queue_type}</Badge>
                        <span className="font-mono text-xs">{j.reason_code}</span>
                        <span className="text-xs text-gray-500">
                          attempts: {j.attempts}
                        </span>
                        <span className="text-xs text-gray-500">
                          {j.created_at}
                        </span>
                      </div>
                      <div className="mt-1 text-sm">{j.reason_message ?? "—"}</div>
                      {j.card_ids?.length ? (
                        <div className="mt-1 text-xs text-gray-600">
                          cards: {j.card_ids.join(", ")}
                        </div>
                      ) : null}
                      {j.payload_json ? (
                        <pre className="mt-2 text-xs bg-gray-50 p-2 rounded-lg overflow-auto">
                          {JSON.stringify(j.payload_json, null, 2)}
                        </pre>
                      ) : null}
                    </div>
                    <div className="flex gap-1">
                      <button
                        className="px-2 py-1 rounded-lg border text-xs hover:bg-gray-50"
                        onClick={() => retryOne(j.id)}
                      >
                        Retry
                      </button>
                      <button
                        className="px-2 py-1 rounded-lg border text-xs hover:bg-gray-50"
                        onClick={() => dropOne(j.id)}
                      >
                        Drop
                      </button>
                    </div>
                  </div>
                </div>
              );
            })}
            {items.length === 0 && (
              <div className="text-sm text-gray-500">DLQ is empty 🎉</div>
            )}
          </div>
        </Card>
      </Section>
    </div>
  );
}

// -----------------------------
// Admin Logs Page
// -----------------------------
export function AdminLogsPage() {
  const [data, setData] = useState<LogsList | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [q, setQ] = useState("");
  const [loading, setLoading] = useState(true);

  const load = () => {
    setLoading(true);
    adminApi.getLogs({ q, limit: 50 })
      .then((r) => (setData(r), setErr(null)))
      .catch((e) => setErr(e.message))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  return (
    <div className="p-4 space-y-4">
      <Section title="System & Audit Logs">
        <div className="flex gap-2">
          <input
            className="flex-1 border rounded-lg px-3 py-2 text-sm"
            placeholder="Search logs (code, message, correlation_id, card_id...)"
            value={q}
            onChange={(e) => setQ(e.target.value)}
            onKeyDown={(e) => e.key === "Enter" && load()}
          />
          <button className="px-3 py-2 rounded-lg border text-sm hover:bg-gray-50" onClick={load}>
            Search
          </button>
        </div>

        {loading && <div className="text-sm">Loading…</div>}
        {err && <div className="text-red-600 text-sm">{err}</div>}

        <Card title={`Results (${data?.total ?? 0})`}>
          <div className="space-y-2">
            {data?.items.map((l) => (
              <div key={String(l.id)} className="border rounded-xl p-3">
                <div className="flex items-center gap-2">
                  <Badge variant={l.level === "error" || l.level === "fatal" ? "fail" : "neutral"}>
                    {l.level ?? "log"}
                  </Badge>
                  {l.code && <span className="font-mono text-xs">{l.code}</span>}
                  {l.action && <span className="text-xs text-gray-600">{l.action}</span>}
                  {l.card_id != null && <span className="text-xs text-gray-600">card {l.card_id}</span>}
                  {l.correlation_id && <span className="font-mono text-xs text-gray-500">{l.correlation_id}</span>}
                  <span className="ml-auto text-xs text-gray-500">{l.ts}</span>
                </div>
                <div className="mt-1 text-sm">{l.message}</div>
                {l.meta_json && (
                  <pre className="mt-2 text-xs bg-gray-50 p-2 rounded-lg overflow-auto">
                    {JSON.stringify(l.meta_json, null, 2)}
                  </pre>
                )}
              </div>
            ))}
            {!data?.items.length && !loading && (
              <div className="text-sm text-gray-500">No logs</div>
            )}
          </div>
        </Card>
      </Section>
    </div>
  );
}

// -----------------------------
// Admin Integrations / Health Page
// -----------------------------
export function AdminIntegrationsPage() {
  const [health, setHealth] = useState<Health | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [busy, setBusy] = useState<string | null>(null);

  const reload = () => {
    adminApi.getHealth()
      .then((h) => (setHealth(h), setErr(null)))
      .catch((e) => setErr(e.message));
  };

  useEffect(() => { reload(); }, []);

  const testCall = async (service: string) => {
    try {
      setBusy(service);
      await adminApi.testIntegration(service);
      reload();
    } catch (e: any) {
      setErr(e.message);
    } finally {
      setBusy(null);
    }
  };

  return (
    <div className="p-4 space-y-4">
      <Section title="Integrations Health">
        {err && <div className="text-red-600 text-sm">{err}</div>}
        {!health && <div className="text-sm">Loading…</div>}
        {health && (
          <IntegrationsTable
            integrations={health.services}
            onTest={testCall}
            busy={busy}
          />
        )}
      </Section>
    </div>
  );
}

function IntegrationsTable(props: {
  integrations: Health["services"];
  onTest?: (service: string) => void;
  busy?: string | null;
}) {
  return (
    <Card title="Services">
      <table className="min-w-full text-sm">
        <thead>
          <tr className="text-left text-gray-600">
            <th className="py-2 pr-3">Service</th>
            <th className="py-2 pr-3">Status</th>
            <th className="py-2 pr-3">Latency</th>
            <th className="py-2 pr-3">Updated</th>
            {props.onTest && <th className="py-2 pr-3">Actions</th>}
          </tr>
        </thead>
        <tbody>
          {props.integrations.map((s) => (
            <tr key={s.service} className="border-t">
              <td className="py-2 pr-3 font-medium">{s.service}</td>
              <td className="py-2 pr-3">
                <Badge
                  variant={
                    s.status === "ok" ? "ok" :
                    s.status === "degraded" ? "warn" :
                    s.status === "fail" ? "fail" : "neutral"
                  }
                >
                  {s.status}
                </Badge>
                <ErrorBox code={s.last_error_code} message={s.last_error_message} />
              </td>
              <td className="py-2 pr-3">{formatLatency(s.latency_ms)}</td>
              <td className="py-2 pr-3 text-xs text-gray-500">{s.updated_at ?? "—"}</td>
              {props.onTest && (
                <td className="py-2 pr-3">
                  <button
                    className="px-3 py-1 rounded-lg border text-xs hover:bg-gray-50 disabled:opacity-50"
                    disabled={props.busy === s.service}
                    onClick={() => props.onTest?.(s.service)}
                  >
                    Test call
                  </button>
                </td>
              )}
            </tr>
          ))}
          {props.integrations.length === 0 && (
            <tr><td colSpan={5} className="py-4 text-gray-500">No services</td></tr>
          )}
        </tbody>
      </table>
    </Card>
  );
}
