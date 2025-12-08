// frontend/src/features/publish/ui/index.tsx
import React, { useEffect, useMemo, useState } from "react";
import { publishApi } from "../api";
import type { PublishTasksList, PublishTask, PublishStatus, PublishRef } from "../schemas";
import {
  PUBLISH_STATUS_LABELS,
  publishStatusVariant,
  allowedPublishActions,
  progressPercent,
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

export function PublishStatusBadge({ status }: { status: PublishStatus }) {
  return (
    <Badge variant={publishStatusVariant(status)}>
      {PUBLISH_STATUS_LABELS[status] ?? status}
    </Badge>
  );
}

// --------------------------------------------------
// Publish tasks list page
// --------------------------------------------------
export function PublishTasksListPage(props: { cardId?: string | number }) {
  const [data, setData] = useState<PublishTasksList | null>(null);
  const [status, setStatus] = useState<string>("");
  const [channel, setChannel] = useState<string>("");
  const [err, setErr] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = () => {
    setLoading(true);
    publishApi.list({
      status: status || undefined,
      channel: channel || undefined,
      card_id: props.cardId,
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
      await publishApi.run({
        card_id: props.cardId,
        channel: channel || undefined,
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
          {Object.entries(PUBLISH_STATUS_LABELS).map(([k, v]) => (
            <option key={k} value={k}>{v}</option>
          ))}
        </select>
        <input
          className="border rounded-xl px-3 py-2 text-sm w-40"
          placeholder="channel (avito…)"
          value={channel}
          onChange={(e) => setChannel(e.target.value)}
        />
        <Button onClick={load}>Apply</Button>
        <Button onClick={onRun}>
          {props.cardId ? "Start Publish for Card" : "Start Publish"}
        </Button>
      </div>

      {loading && <div className="text-sm">Loading publish tasks…</div>}
      {err && <div className="text-sm text-red-600">Error: {err}</div>}

      <div className="space-y-2">
        {items.map((t) => (
          <PublishTaskRow key={String(t.id)} task={t} onChanged={load} />
        ))}
        {!loading && items.length === 0 && (
          <div className="text-sm text-gray-500">No publish tasks</div>
        )}
      </div>
    </div>
  );
}

function PublishTaskRow(props: { task: PublishTask; onChanged: () => void }) {
  const { task } = props;

  const actions = useMemo(
    () => allowedPublishActions(task.status),
    [task.status]
  );

  const primaryProgress = task.progress?.[task.progress.length - 1];
  const pct = progressPercent(primaryProgress);

  const doAction = async (a: string) => {
    try {
      if (a === "retry") await publishApi.retry(task.id, {});
      if (a === "cancel") await publishApi.cancel(task.id, {});
      if (a === "unpublish") {
        try { await publishApi.unpublish(task.id, {}); } catch {}
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
          <div className="font-semibold">PublishTask #{task.id}</div>
          <PublishStatusBadge status={task.status} />
          {task.card_id != null && (
            <div className="text-xs text-gray-500">card {task.card_id}</div>
          )}
          {task.channel && (
            <div className="text-xs text-gray-500">channel {task.channel}</div>
          )}
        </div>

        {primaryProgress && (
          <div className="mt-1">
            <div className="flex items-center justify-between text-xs text-gray-600">
              <span>{primaryProgress.step}</span>
              <span>{primaryProgress.done}/{primaryProgress.total} ({pct}%)</span>
            </div>
            <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
              <div className="h-full bg-gray-400" style={{ width: `${pct}%` }} />
            </div>
            {primaryProgress.message && (
              <div className="text-xs text-gray-500 mt-1">{primaryProgress.message}</div>
            )}
          </div>
        )}

        {task.error_message && (
          <div className="text-xs text-red-600">
            {task.error_code && <span className="font-mono mr-1">{task.error_code}</span>}
            {task.error_message}
          </div>
        )}

        {task.publish_refs?.length ? (
          <PublishRefsList refs={task.publish_refs} />
        ) : null}

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
          href={`/publish/tasks/${task.id}`}
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
// Publish task details page
// --------------------------------------------------
export function PublishTaskDetailsPage({ taskId }: { taskId: string | number }) {
  const [task, setTask] = useState<PublishTask | null>(null);
  const [err, setErr] = useState<string | null>(null);

  const load = () => {
    publishApi.get(taskId)
      .then((t) => (setTask(t), setErr(null)))
      .catch((e) => setErr(e.message));
  };

  useEffect(() => { load(); }, [String(taskId)]);

  if (!task) {
    return <div className="p-4">{err ? `Error: ${err}` : "Loading publish task…"}</div>;
  }

  const actions = allowedPublishActions(task.status);

  const doAction = async (a: string) => {
    try {
      if (a === "retry") await publishApi.retry(task.id, {});
      if (a === "cancel") await publishApi.cancel(task.id, {});
      if (a === "unpublish") {
        try { await publishApi.unpublish(task.id, {}); } catch {}
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
          <div className="text-xl font-bold">PublishTask #{task.id}</div>
          <PublishStatusBadge status={task.status} />
          <div className="ml-auto text-xs text-gray-500">
            attempts: {task.attempts}
          </div>
        </div>

        {task.card_id != null && (
          <div className="mt-1 text-sm">
            Card: <a className="underline" href={`/cards/${task.card_id}`}>{task.card_id}</a>
          </div>
        )}
        {task.channel && (
          <div className="mt-1 text-sm">
            Channel: <b>{task.channel}</b>
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

      {task.progress?.length ? (
        <Box title="Progress">
          <ul className="space-y-2 text-sm">
            {task.progress.map((p, i) => (
              <li key={i} className="border rounded-lg p-2">
                <div className="flex justify-between">
                  <div className="font-medium">{p.step}</div>
                  <div className="text-xs text-gray-600">
                    {p.done}/{p.total} ({progressPercent(p)}%)
                  </div>
                </div>
                {p.message && <div className="text-xs text-gray-600 mt-1">{p.message}</div>}
              </li>
            ))}
          </ul>
        </Box>
      ) : null}

      {task.publish_refs?.length ? (
        <Box title="Publish refs">
          <PublishRefsList refs={task.publish_refs} expanded />
        </Box>
      ) : null}

      {task.payload_json && (
        <Box title="Payload (input)">
          <pre className="text-xs bg-gray-50 p-2 rounded-lg overflow-auto">
            {JSON.stringify(task.payload_json, null, 2)}
          </pre>
        </Box>
      )}

      {task.result_json && (
        <Box title="Result (raw)">
          <pre className="text-xs bg-gray-50 p-2 rounded-lg overflow-auto">
            {JSON.stringify(task.result_json, null, 2)}
          </pre>
        </Box>
      )}
    </div>
  );
}

// --------------------------------------------------
function PublishRefsList({ refs, expanded=false }: { refs: PublishRef[]; expanded?: boolean }) {
  return (
    <div className="mt-2 space-y-1">
      {refs.map((r, i) => (
        <div key={i} className="text-xs">
          <span className="font-mono">{r.channel}</span>
          <span className="mx-1 text-gray-400">•</span>
          <span className="font-mono">{r.external_id}</span>
          {r.url && (
            <>
              <span className="mx-1 text-gray-400">•</span>
              <a className="underline" href={r.url} target="_blank" rel="noreferrer">
                open
              </a>
            </>
          )}
          {expanded && r.meta && (
            <pre className="mt-1 text-[10px] bg-gray-50 p-1 rounded">
              {JSON.stringify(r.meta, null, 2)}
            </pre>
          )}
        </div>
      ))}
    </div>
  );
}

function actionLabel(a: string) {
  switch (a) {
    case "start": return "Start";
    case "retry": return "Retry";
    case "cancel": return "Cancel";
    case "unpublish": return "Unpublish";
    case "open_card": return "Open Card";
    default: return a;
  }
}
