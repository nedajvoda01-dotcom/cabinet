import React from "react";

export function ToggleRow({ label, value }: { label: string; value: boolean }) {
  return (
    <label className="flex items-center gap-2 text-sm">
      <input type="checkbox" checked={value} readOnly />
      <span>{label}</span>
    </label>
  );
}
