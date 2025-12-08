// frontend/src/features/photos/ui/index.tsx
import React, { useEffect, useMemo, useState } from "react";
import { photosApi } from "../api";
import type { PhotosTasksList, PhotosTask, PhotosStatus, PhotoArtifact } from "../schemas";
import {
  PHOTOS_STATUS_LABELS,
  photosStatusVariant,
  allowedPhotosActions,
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

export function PhotosStatusBadge({ status }: { status: PhotosStatus }) {
  return (
    <Badge variant={photosStatusVariant(status)}>
      {PHOTOS_STATUS_LABELS[status] ?? status}
    </Badge>
  );
}

// --------------------------------------------------
// Photos tasks list page
// --------------------------------------------------
export function PhotosTasksListPage(props: { cardId?: string | number }) {
  const [data, setData] = useState<PhotosTasksList | null>(null);
  const [status, setStatus] = useState<string>("");
  const [err, setErr] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = () => {
    setLoading(true);
    photosApi.list({
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

  const onRun = async () => {
    try {
      await photosApi.run({ card_id: props.cardId });
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
          {Object.entries(PHOTOS_STATUS_LABELS).map(([k, v]) => (
            <option key={k} value={k}>{v}</option>
          ))}
        </select>
        <Button onClick={load}>Apply</Button>
        <Button onClick={onRun}>
          {props.cardId ? "Start Photos for Card" : "Start Photos"}
        </Button>
      </div>

      {loading && <div className="text-sm">Loading photos tasks…</div>}
      {err && <div className="text-sm text-red-600">Error: {err}</div>}

      <div className="space-y-2">
        {items.map((t) => (
          <PhotosTaskRow key={String(t.id)} task={t} onChanged={load} />
        ))}
        {!loading && items.length === 0 && (
          <div className="text-sm text-gray-500">No photos tasks</div>
        )}
      </div>
    </div>
  );
}

function PhotosTaskRow(props: { task: PhotosTask; onChanged: () => void }) {
  const { task } = props;

  const actions = useMemo(
    () => allowedPhotosActions(task.status),
    [task.status]
  );

  const primaryProgress = task.progress?.[task.progress.length - 1];
  const pct = progressPercent(primaryProgress);

  const doAction = async (a: string) => {
    try {
      if (a === "retry") await photosApi.retry(task.id, {});
      if (a === "cancel") {
        try { await photosApi.cancel(task.id, {}); } catch {}
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
          <div className="font-semibold">PhotosTask #{task.id}</div>
          <PhotosStatusBadge status={task.status} />
          {task.card_id != null && (
            <div className="text-xs text-gray-500">card {task.card_id}</div>
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
          href={`/photos/tasks/${task.id}`}
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
        {task.card_id != null && (
          <a
            href={`/photos/card/${task.card_id}`}
            className="text-xs underline text-gray-600 hover:text-black"
          >
            Artifacts
          </a>
        )}
      </div>
    </Box>
  );
}

// --------------------------------------------------
// Photos task details page
// --------------------------------------------------
export function PhotosTaskDetailsPage({ taskId }: { taskId: string | number }) {
  const [task, setTask] = useState<PhotosTask | null>(null);
  const [err, setErr] = useState<string | null>(null);

  const load = () => {
    photosApi.get(taskId)
      .then((t) => (setTask(t), setErr(null)))
      .catch((e) => setErr(e.message));
  };

  useEffect(() => { load(); }, [String(taskId)]);

  if (!task) {
    return <div className="p-4">{err ? `Error: ${err}` : "Loading photos task…"}</div>;
  }

  const actions = allowedPhotosActions(task.status);

  const doAction = async (a: string) => {
    try {
      if (a === "retry") await photosApi.retry(task.id, {});
      if (a === "cancel") {
        try { await photosApi.cancel(task.id, {}); } catch {}
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
          <div className="text-xl font-bold">PhotosTask #{task.id}</div>
          <PhotosStatusBadge status={task.status} />
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

      {task.payload_json && (
        <Box title="Payload (input)">
          <pre className="text-xs bg-gray-50 p-2 rounded-lg overflow-auto">
            {JSON.stringify(task.payload_json, null, 2)}
          </pre>
        </Box>
      )}

      {task.result_json && (
        <Box title="Result (artifacts)">
          <pre className="text-xs bg-gray-50 p-2 rounded-lg overflow-auto">
            {JSON.stringify(task.result_json, null, 2)}
          </pre>
        </Box>
      )}

      {task.card_id != null && (
        <PhotosArtifactsPanel cardId={task.card_id} />
      )}
    </div>
  );
}

// --------------------------------------------------
// Card photos artifacts panel
// --------------------------------------------------
export function PhotosArtifactsPanel({ cardId }: { cardId: string | number }) {
  const [items, setItems] = useState<PhotoArtifact[]>([]);
  const [err, setErr] = useState<string | null>(null);

  const load = () => {
    photosApi.listForCard(cardId)
      .then((r) => (setItems(r.items), setErr(null)))
      .catch((e) => setErr(e.message));
  };

  useEffect(() => { load(); }, [String(cardId)]);

  const onDelete = async (photoId: string | number) => {
    try {
      await photosApi.deletePhoto(photoId);
      load();
    } catch (e: any) {
      setErr(e.message);
    }
  };

  const onSetPrimary = async (photoId: string | number) => {
    try {
      await photosApi.setPrimary(cardId, { photo_id: photoId });
      load();
    } catch (e: any) {
      setErr(e.message);
    }
  };

  return (
    <Box title={`Card ${cardId} photos`}>
      {err && <div className="text-sm text-red-600 mb-2">{err}</div>}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
        {items.map((p) => (
          <div key={String(p.id)} className="rounded-xl overflow-hidden bg-gray-100 relative">
            {(p.masked_url || p.raw_url) ? (
              <img
                src={(p.masked_url || p.raw_url)!}
                className="w-full h-40 object-cover"
                alt=""
              />
            ) : null}
            <div className="absolute inset-x-0 bottom-0 bg-white/80 p-1 flex gap-1 justify-between text-xs">
              <span className="font-mono">#{p.order ?? "—"}</span>
              <div className="flex gap-1">
                <Button className="px-2 py-0.5" onClick={() => onSetPrimary(p.id)}>Primary</Button>
                <Button className="px-2 py-0.5" onClick={() => onDelete(p.id)}>Delete</Button>
              </div>
            </div>
          </div>
        ))}
        {items.length === 0 && (
          <div className="text-sm text-gray-500">No photos</div>
        )}
      </div>
    </Box>
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
