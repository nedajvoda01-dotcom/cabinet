// cabinet/frontend/src/features/export/ui/index.tsx
import React, { useEffect, useMemo, useState } from "react";
import { exportApi } from "../../../features/export/api";
import type { ExportList, ExportTask, ExportStatus } from "../../../features/export/schemas";
import {
  EXPORT_STATUS_LABELS,
  exportStatusVariant,
  allowedExportActions,
  formatBytes,
  formatFormat,
} from "../../../features/export/model";

// --------- atoms ---------
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

export function ExportStatusBadge({ status }: { status: ExportStatus }) {
  return (
    <Badge variant={exportStatusVariant(status)}>
      {EXPORT_STATUS_LABELS[status] ?? status}
    </Badge>
  );
}

// --------------------------------------------------
// Export List Page
// --------------------------------------------------
export function ExportListPage(props: { cardId?: string | number }) {
  const [data, setData] = useState<ExportList | null>(null);
  const [status, setStatus] = useState<string>("");
  const [err, setErr] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = () => {
    setLoading(true);
    exportApi.list({
      status: status || undefined,
      card_id: props.cardId,
      limit: 50,
    })
      .then((r) => (setData(r), setErr(null)))
      .catch((e) => setErr(e.message))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, [String(props.cardId ?? "")]);

  const items = data?.items ?? [];

  const onCreate = async () => {
    try {
      await exportApi.create({
        card_id: props.cardId,
        format: "csv",
      });
      load();
    } catch (e: any) {
      setErr(e.message);
    }
  };

  return (
    <div className="p-4 space-y-4">
      <div className="flex flex-wrap items-center gap-2">
        <select
          className="border rounded-xl px-3 py-2 text-sm"
          value={status}
          onChange={(e) => setStatus(e.target.value)}
        >
          <option value="">All statuses</option>
          {Object.entries(EXPORT_STATUS_LABELS).map(([k, v]) => (
            <option key={k} value={k}>{v}</option>
          ))}
        </select>
        <Button onClick={load}>Apply</Button>
        <Button onClick={onCreate}>
          {props.cardId ? "Start Export for Card" : "Start Export"}
        </Button>
      </div>

      {loading && <div className="text-sm">Loading exports…</div>}
      {err && <div className="text-sm text-red-600">Error: {err}</div>}

      <div className="space-y-2">
        {items.map((t) => (
          <ExportRow key={String(t.id)} task={t} onChanged={load} />
        ))}
        {!loading && items.length === 0 && (
          <div className="text-sm text-gray-500">No exports</div>
        )}
      </div>
    </div>
  );
}

function ExportRow(props: { task: ExportTask; onChanged: () => void }) {
  const { task } = props;

  const actions = useMemo(
    () => allowedExportActions(task.status),
    [task.status]
  );

  const doAction = async (a: string) => {
    try {
      if (a === "cancel") await exportApi.cancel(task.id, {});
      if (a === "retry") await exportApi.retry(task.id, {});
      if (a === "download") {
        const blob = await exportApi.download(task.id);
        const url = URL.createObjectURL(blob);
        const aEl = document.createElement("a");
        aEl.href = url;
        aEl.download = task.file_name || `export_${task.id}.${(task.format ?? "csv")}`;
        aEl.click();
        URL.revokeObjectURL(url);
      }
      props.onChanged();
    } catch (e) {
      // eslint-disable-next-line no-console
      console.error(e);
    }
  };

  return (
    <Box className="flex items-start gap-3">
      <div className="flex-1 space-y-1">
        <div className="flex items-center gap-2">
          <div className="font-semibold">Export #{task.id}</div>
          <ExportStatusBadge status={task.status} />
          {task.card_id != null && (
            <div className="text-xs text-gray-500">card {task.card_id}</div>
          )}
        </div>

        <div className="text-sm text-gray-700">
          format: {formatFormat(task.format)}
          {task.file_size != null && <> • size: {formatBytes(task.file_size)}</>}
        </div>

        {task.error_message && (
          <div className="text-xs text-red-600">
            {task.error_code && <span className="font-mono mr-1">{task.error_code}</span>}
            {task.error_message}
          </div>
        )}

        <div className="text-xs text-gray-400">
          attempts: {task.attempts} • created: {task.created_at} • updated: {task.updated_at}
        </div>

        {task.params && (
          <pre className="text-xs bg-gray-50 p-2 rounded-lg overflow-auto mt-2">
            {JSON.stringify(task.params, null, 2)}
          </pre>
        )}
      </div>

      <div className="flex flex-col gap-1">
        {actions.map((a) => (
          <Button key={a} onClick={() => doAction(a)}>
            {actionLabel(a)}
          </Button>
        ))}
        <a
          href={`/export/${task.id}`}
          className="text-xs underline text-gray-600 hover:text-black mt-1"
        >
          Open
        </a>
      </div>
    </Box>
  );
}

// --------------------------------------------------
// Export Details Page
// --------------------------------------------------
export function ExportDetailsPage({ exportId }: { exportId: string | number }) {
  const [task, setTask] = useState<ExportTask | null>(null);
  const [err, setErr] = useState<string | null>(null);

  const load = () => {
    exportApi.get(exportId)
      .then((t) => (setTask(t), setErr(null)))
      .catch((e) => setErr(e.message));
  };

  useEffect(() => { load(); }, [String(exportId)]);

  if (!task) {
    return <div className="p-4">{err ? `Error: ${err}` : "Loading export…"}</div>;
  }

  const actions = allowedExportActions(task.status);

  const doAction = async (a: string) => {
    try {
      if (a === "cancel") await exportApi.cancel(task.id, {});
      if (a === "retry") await exportApi.retry(task.id, {});
      if (a === "download") {
        const blob = await exportApi.download(task.id);
        const url = URL.createObjectURL(blob);
        const aEl = document.createElement("a");
        aEl.href = url;
        aEl.download = task.file_name || `export_${task.id}.${(task.format ?? "csv")}`;
        aEl.click();
        URL.revokeObjectURL(url);
      }
      load();
    } catch (e: any) {
      setErr(e.message);
    }
  };

  return (
    <div className="p-4 space-y-4">
      {err && <div className="text-sm text-red-600">{err}</div>}

      <Box>
        <div className="flex items-center gap-2">
          <div className="text-xl font-bold">Export #{task.id}</div>
          <ExportStatusBadge status={task.status} />
          <div className="ml-auto text-sm text-gray-500">
            {formatFormat(task.format)} • {formatBytes(task.file_size)}
          </div>
        </div>

        {task.card_id != null && (
          <div className="mt-1 text-sm text-gray-700">
            Card: <a className="underline" href={`/cards/${task.card_id}`}>{task.card_id}</a>
          </div>
        )}

        {task.file_url && (
          <div className="mt-2 text-sm">
            file_url: <a href={task.file_url} className="underline">{task.file_url}</a>
          </div>
        )}

        {task.error_message && (
          <div className="mt-3 rounded-xl bg-red-50 border border-red-200 p-2 text-sm">
            <div className="font-mono text-xs text-red-700">{task.error_code}</div>
            <div className="text-red-900">{task.error_message}</div>
          </div>
        )}

        {actions.length > 0 && (
          <div className="mt-3 flex gap-2 flex-wrap">
            {actions.map((a) => (
              <Button key={a} onClick={() => doAction(a)}>
                {actionLabel(a)}
              </Button>
            ))}
          </div>
        )}
      </Box>

      {task.params && (
        <Box title="Params">
          <pre className="text-xs bg-gray-50 p-2 rounded-lg overflow-auto">
            {JSON.stringify(task.params, null, 2)}
          </pre>
        </Box>
      )}
    </div>
  );
}

function actionLabel(a: string) {
  switch (a) {
    case "start": return "Start";
    case "retry": return "Retry";
    case "cancel": return "Cancel";
    case "download": return "Download";
    default: return a;
  }
}
