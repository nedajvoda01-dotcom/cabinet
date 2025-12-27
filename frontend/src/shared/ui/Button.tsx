import React from "react";

type Props = React.ButtonHTMLAttributes<HTMLButtonElement> & { variant?: "primary" | "ghost" };

export function Button({ variant = "primary", className = "", ...rest }: Props) {
  const base =
    variant === "primary"
      ? "bg-blue-600 text-white border-blue-600"
      : "bg-white text-slate-800 border-slate-200";
  return (
    <button
      {...rest}
      className={`px-3 py-2 rounded-lg border text-sm font-medium ${base} ${className}`}
    />
  );
}
