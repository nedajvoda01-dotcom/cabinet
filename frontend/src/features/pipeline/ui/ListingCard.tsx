import React from "react";
import type { Card } from "../../cards/schemas";
import { StatusPill } from "./StatusPill";

export function ListingCard({ card }: { card: Card }) {
  return (
    <div className="border border-slate-200 rounded-xl p-3 bg-white">
      <div className="flex items-center gap-2">
        <div className="font-semibold">Card #{card.id}</div>
        <StatusPill status={card.status} />
      </div>
      <div className="text-xs text-slate-500">{card.vehicle?.make} {card.vehicle?.model}</div>
    </div>
  );
}
