import React from "react";

export function Toast({ message }: { message: string }) {
  return (
    <div className="fixed bottom-4 right-4 bg-slate-900 text-white rounded-lg px-3 py-2 text-sm shadow-lg">
      {message}
    </div>
  );
}
