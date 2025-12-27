import React from "react";

export function Modal({ open, onClose, children }: { open: boolean; onClose: () => void; children: React.ReactNode }) {
  if (!open) return null;
  return (
    <div className="fixed inset-0 bg-black/40 flex items-center justify-center">
      <div className="bg-white rounded-xl shadow-lg min-w-[320px] p-4">
        <button className="float-right" onClick={onClose} aria-label="close">
          ×
        </button>
        <div className="clear-both" />
        {children}
      </div>
    </div>
  );
}
