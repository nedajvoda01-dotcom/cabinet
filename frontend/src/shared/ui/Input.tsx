import React from "react";

export function Input(props: React.InputHTMLAttributes<HTMLInputElement>) {
  return <input {...props} className={`border rounded-lg px-3 py-2 text-sm ${props.className ?? ""}`} />;
}
