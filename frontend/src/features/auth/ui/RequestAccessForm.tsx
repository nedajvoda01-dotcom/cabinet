import React from "react";
import { Button } from "../../shared/ui/Button";
import { Input } from "../../shared/ui/Input";

export function RequestAccessForm() {
  return (
    <div className="space-y-3 bg-white p-4 rounded-xl border border-slate-200">
      <div className="text-sm text-slate-600">Request access to the workspace</div>
      <Input placeholder="your email" className="w-full" />
      <Button type="button">Submit request</Button>
    </div>
  );
}
