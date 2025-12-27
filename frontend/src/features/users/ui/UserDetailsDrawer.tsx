import React from "react";
import type { UserRow } from "../model";

export function UserDetailsDrawer({ user }: { user: UserRow }) {
  return (
    <div className="p-3 text-sm">
      <div>{user.email}</div>
      <div className="text-slate-500">Role: {user.role}</div>
    </div>
  );
}
