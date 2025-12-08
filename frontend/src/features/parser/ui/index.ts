// frontend/src/features/parser/ui/index.tsx
import React, { useEffect, useMemo, useState } from "react";
import { parserApi } from "../api";
import type { ParserTasksList, ParserTask, ParserStatus } from "../schemas";
import {
  PARSER_STATUS_LABELS,
  parserStatusVariant,
  allowedParserActions,
  shortJson,
} from "../model";

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

export function ParserStatusBadge({ status }: { status: ParserStatus }) {
  return (
    <Badge variant={parserStatusVariant(status)}>
      {PARSER_STATUS_LABELS[status] ?? status}
    </Badge>
  );
}

// --------------------------------------------------
// Parser tasks list page
// --------------------------------------------------
export function ParserTasksListPage(props: { cardId?: string | number }) {
  const [data, setData] = useState<ParserTasksList | null>(null);
  const [status, setStatus] = useState<string>("");
  const [q, setQ] = useState("");
  const [err, setErr] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = () => {
    setLoading(true);
    parserApi.list({
      status: status || undefined,
      card_id: props.cardId,
      q: q || undefined,
      limit: 50,
    })
      .then((r) => (setData(r), setErr(null)))
      .catch((e) => setErr(e.message))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, [String(props.cardId ?? "")]);

  const items = data?.items ?? [];

  const onRun = async () => {
    try {
      await parserApi.run({
        card_id: props.cardId,
      });
      load();
    } catch (e: any) {
      setErr(e.message);
    }
  };

  return (
    <div className="p-4 space-y-4">
      <div className="flex flex-wrap items-center gap-2">
        <input
          className="border rounded-xl px-3 py-2 text-sm w-72"
          placeholder="Search by source/source_id/text…"
          value={q}
          onChange={(e) => setQ(e.target.value)}
          onKeyDown={(e) => e.key === "Enter" && load()}
        />
        <select
          className="border rounded-xl px-3 py-2 text-sm"
          value={status}
          onChange={(e) => setStatus(e.target.value)}
        >
          <option value="">All statuses</option>
          {Object.entries(PARSER_STATUS_LABELS).map(([k, v]) => (
            <option key={k} value={k}>{v}</option>
          ))}
        </select>
        <Button onClick={load}>Apply</Button>
        <Button onClick={onRun}>
          {props.cardId ? "Run Parser for Card" : "Run Parser"}
        </Button>
      </div>

      {loading && <div className="text-sm">Loading parser tasks…</div>}
      {err && <div className="text-sm text-red-600">Error: {err}</div>}

      <div className="space-y-2">
        {items.map((t) => (
          <ParserTaskRow key={String(t.id)} task={t} onChanged={load} />
        ))}
        {!loading && items.length === 0 && (
          <div className="text-sm text-gray-500">No parser tasks</div>
        )}
      </div>
    </div>
  );
}

function ParserTaskRow(props: { task: ParserTask; onChanged: () => void }) {
  const { task } = props;

  const actions = useMemo(
    () => allowedParserActions(task.status),
    [task.status]
  );

  const doAction = async (a: string) => {
    try {
      if (a === "retry") await parserApi.retry(task.id, {});
      if (a === "cancel") {
        // cancel optional; swallow if backend doesn't support
        try { await parserApi.cancel(task.id, {}); } catch {}
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
          <div className="font-semibold">ParserTask #{task.id}</div>
          <ParserStatusBadge status={task.status} />
          {task.card_id != null && (
            <div className="text-xs text-gray-500">card {task.card_id}</div>
          )}
          {task.source && (
            <div className="text-xs text-gray-500">source {task.source}</div>
          )}
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

        {task.payload_json && (
          <pre className="text-xs bg-gray-50 p-2 rounded-lg overflow-auto mt-2">
            {shortJson(task.payload_json)}
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
          href={`/parser/tasks/${task.id}`}
          className="text-xs underline text-gray-600 hover:text-black mt-1"
        >
          Open
        </a>
        {task.card_id != null && (
          <a
            href={`/cards/${task.card_id}`}
            className="text-xs underline text-gray-600 hover:text-black"
          >
            Card
          </a>
        )}
      </div>
    </Box>
  );
}

// --------------------------------------------------
// Parser task details page
// --------------------------------------------------
export function ParserTaskDetailsPage({ taskId }: { taskId: string | number }) {
  const [task, setTask] = useState<ParserTask | null>(null);
  const [err, setErr] = useState<string | null>(null);

  const load = () => {
    parserApi.get(taskId)
      .then((t) => (setTask(t), setErr(null)))
      .catch((e) => setErr(e.message));
  };

  useEffect(() => { load(); }, [String(taskId)]);

  if (!task) {
    return <div className="p-4">{err ? `Error: ${err}` : "Loading parser task…"}</div>;
  }

  const actions = allowedParserActions(task.status);

  const doAction = async (a: string) => {
    try {
      if (a === "retry") await parserApi.retry(task.id, {});
      if (a === "cancel") {
        try { await parserApi.cancel(task.id, {}); } catch {}
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
          <div className="text-xl font-bold">ParserTask #{task.id}</div>
          <ParserStatusBadge status={task.status} />
          <div className="ml-auto text-xs text-gray-500">
            attempts: {task.attempts}
          </div>
        </div>

        {task.card_id != null && (
          <div className="mt-1 text-sm">
            Card: <a className="underline" href={`/cards/${task.card_id}`}>{task.card_id}</a>
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

      {task.payload_json && (
        <Box title="Payload (input)">
          <pre className="text-xs bg-gray-50 p-2 rounded-lg overflow-auto">
            {JSON.stringify(task.payload_json, null, 2)}
          </pre>
        </Box>
      )}

      {task.result_json && (
        <Box title="Result (normalized snapshot)">
          <pre className="text-xs bg-gray-50 p-2 rounded-lg overflow-auto">
            {JSON.stringify(task.result_json, null, 2)}
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
    case "open_card": return "Open Card";
    default: return a;
  }
}
