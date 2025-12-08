// cabinet/frontend/src/features/cards/ui/index.tsx
import React, { useEffect, useMemo, useState } from "react";
import { cardsApi } from "../api";
import type { Card, CardStatus, CardsList } from "../schemas";
import {
  STATUS_LABELS,
  allowedActions,
  statusVariant,
  formatVehicle,
  formatPrice,
} from "../model";

// -----------------------------
// small UI atoms
// -----------------------------
function CardBox(props: { title?: string; children: React.ReactNode; className?: string }) {
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

export function StatusBadge({ status }: { status: CardStatus }) {
  return <Badge variant={statusVariant(status)}>{STATUS_LABELS[status] ?? status}</Badge>;
}

function PrimaryButton(props: React.ButtonHTMLAttributes<HTMLButtonElement>) {
  return (
    <button
      {...props}
      className={`px-3 py-2 rounded-xl text-sm font-medium border hover:bg-gray-50 disabled:opacity-50 ${props.className ?? ""}`}
    />
  );
}

// -----------------------------
// Cards List Page
// -----------------------------
export function CardsListPage() {
  const [data, setData] = useState<CardsList | null>(null);
  const [status, setStatus] = useState<string>(""); // filter
  const [q, setQ] = useState("");
  const [err, setErr] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = () => {
    setLoading(true);
    cardsApi.list({ status: status || undefined, q: q || undefined, limit: 50 })
      .then((r) => (setData(r), setErr(null)))
      .catch((e) => setErr(e.message))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const items = data?.items ?? [];

  return (
    <div className="p-4 space-y-4">
      <div className="flex flex-wrap gap-2 items-center">
        <input
          className="border rounded-xl px-3 py-2 text-sm w-72"
          placeholder="Search by make/model/vin/source_id…"
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
          {Object.entries(STATUS_LABELS).map(([k, v]) => (
            <option key={k} value={k}>{v}</option>
          ))}
        </select>
        <PrimaryButton onClick={load}>Apply</PrimaryButton>
      </div>

      {loading && <div className="text-sm">Loading cards…</div>}
      {err && <div className="text-sm text-red-600">Error: {err}</div>}

      <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
        {items.map((c) => (
          <CardRow key={String(c.id)} card={c} />
        ))}
        {!loading && items.length === 0 && (
          <div className="text-sm text-gray-500">No cards</div>
        )}
      </div>
    </div>
  );
}

function CardRow({ card }: { card: Card }) {
  return (
    <CardBox className="flex gap-3">
      <div className="w-28 h-20 bg-gray-100 rounded-xl overflow-hidden flex items-center justify-center text-xs text-gray-500">
        {card.photos?.[0]?.masked_url || card.photos?.[0]?.raw_url ? (
          <img
            src={(card.photos[0].masked_url || card.photos[0].raw_url)!}
            alt=""
            className="w-full h-full object-cover"
          />
        ) : "no photo"}
      </div>

      <div className="flex-1 space-y-1">
        <div className="flex items-center gap-2">
          <div className="font-semibold">{formatVehicle(card.vehicle)}</div>
          <StatusBadge status={card.status} />
        </div>
        <div className="text-sm text-gray-700">{formatPrice(card.price)}</div>
        {card.location?.city && (
          <div className="text-xs text-gray-500">{card.location.city}</div>
        )}
        {card.last_error_message && (
          <div className="text-xs text-red-600 line-clamp-1">
            {card.last_error_message}
          </div>
        )}
        <div className="text-xs text-gray-400">
          id: {card.id} • updated: {card.updated_at}
        </div>
      </div>

      {/* Navigation link placeholder */}
      <a
        href={`/cards/${card.id}`}
        className="self-start text-sm underline text-gray-700 hover:text-black"
      >
        Open
      </a>
    </CardBox>
  );
}

// -----------------------------
// Card Details Page
// -----------------------------
export function CardDetailsPage({ cardId }: { cardId: string | number }) {
  const [card, setCard] = useState<Card | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const load = () => {
    cardsApi.get(cardId)
      .then((c) => (setCard(c), setErr(null)))
      .catch((e) => setErr(e.message));
  };

  useEffect(() => { load(); }, [String(cardId)]);

  const actions = useMemo(
    () => (card ? allowedActions(card.status) : []),
    [card?.status]
  );

  const runAction = async (a: ReturnType<typeof allowedActions>[number]) => {
    if (!card) return;
    try {
      setBusy(true);
      if (a === "start_photos" || a === "retry_photos") {
        await cardsApi.startOrRetryPhotos(card.id);
      } else if (a === "start_export" || a === "retry_export") {
        await cardsApi.startOrRetryExport(card.id);
      } else if (a === "start_publish" || a === "retry_publish") {
        await cardsApi.startOrRetryPublish(card.id);
      }
      load();
    } catch (e: any) {
      setErr(e.message);
    } finally {
      setBusy(false);
    }
  };

  if (!card) {
    return <div className="p-4">{err ? `Error: ${err}` : "Loading card…"}</div>;
  }

  return (
    <div className="p-4 space-y-4">
      {err && <div className="text-sm text-red-600">Error: {err}</div>}

      <CardBox>
        <div className="flex flex-wrap items-center gap-2">
          <div className="text-xl font-bold">{formatVehicle(card.vehicle)}</div>
          <StatusBadge status={card.status} />
          <div className="ml-auto text-sm text-gray-500">
            {formatPrice(card.price)}
          </div>
        </div>
        {card.description && (
          <div className="mt-2 text-sm whitespace-pre-wrap">{card.description}</div>
        )}

        {card.last_error_message && (
          <div className="mt-3 rounded-xl bg-red-50 border border-red-200 p-2 text-sm">
            <div className="font-mono text-xs text-red-700">{card.last_error_code}</div>
            <div className="text-red-900">{card.last_error_message}</div>
          </div>
        )}

        {actions.length > 0 && (
          <div className="mt-3 flex flex-wrap gap-2">
            {actions.map((a) => (
              <PrimaryButton key={a} disabled={busy} onClick={() => runAction(a)}>
                {actionLabel(a)}
              </PrimaryButton>
            ))}
          </div>
        )}
      </CardBox>

      <CardBox title="Photos">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
          {(card.photos ?? []).map((p) => (
            <div key={String(p.id)} className="rounded-xl overflow-hidden bg-gray-100 h-32">
              {(p.masked_url || p.raw_url) ? (
                <img
                  src={(p.masked_url || p.raw_url)!}
                  className="w-full h-full object-cover"
                  alt=""
                />
              ) : null}
            </div>
          ))}
          {(card.photos ?? []).length === 0 && (
            <div className="text-sm text-gray-500">No photos</div>
          )}
        </div>
      </CardBox>

      <CardBox title="Details">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
          <div><b>Source:</b> {card.source}</div>
          <div><b>Source ID:</b> {card.source_id ?? "—"}</div>
          <div><b>City:</b> {card.location?.city ?? "—"}</div>
          <div><b>Address:</b> {card.location?.address ?? "—"}</div>
          <div><b>Mileage:</b> {card.vehicle.mileage ?? "—"}</div>
          <div><b>VIN:</b> {card.vehicle.vin ?? "—"}</div>
        </div>
      </CardBox>
    </div>
  );
}

function actionLabel(a: string) {
  switch (a) {
    case "start_photos": return "Start Photos";
    case "retry_photos": return "Retry Photos";
    case "start_export": return "Start Export";
    case "retry_export": return "Retry Export";
    case "start_publish": return "Start Publish";
    case "retry_publish": return "Retry Publish";
    default: return a;
  }
}
