import React from "react";
import { ToggleRow } from "./ToggleRow";

export function SettingsPage() {
  return (
    <div className="p-4 space-y-3">
      <h1 className="text-xl font-semibold">Settings</h1>
      <ToggleRow label="Notifications" value />
    </div>
  );
}
