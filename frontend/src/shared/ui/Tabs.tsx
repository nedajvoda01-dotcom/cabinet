import React from "react";

export function Tabs({
  tabs,
  active,
  onChange,
}: {
  tabs: { id: string; label: string }[];
  active: string;
  onChange: (id: string) => void;
}) {
  return (
    <div className="flex gap-2 border-b border-slate-200">
      {tabs.map((t) => (
        <button
          key={t.id}
          onClick={() => onChange(t.id)}
          className={`px-3 py-2 text-sm border-b-2 ${
            active === t.id ? "border-blue-600 font-semibold" : "border-transparent text-slate-500"
          }`}
        >
          {t.label}
        </button>
      ))}
    </div>
  );
}
