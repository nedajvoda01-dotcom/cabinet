import React from "react";
import { usersApi } from "../api";
import type { UserRow } from "../model";
import { Table } from "../../shared/ui/Table";
import { RoleBadge } from "./RoleBadge";

export function UsersPage() {
  const [items, setItems] = React.useState<UserRow[]>([]);

  React.useEffect(() => {
    usersApi.list().then(setItems);
  }, []);

  return (
    <div className="p-4 space-y-3">
      <h1 className="text-xl font-semibold">Users</h1>
      <Table
        headers={["Email", "Role"]}
        rows={items.map((u) => [u.email, <RoleBadge key={u.id} role={u.role} />])}
      />
    </div>
  );
}
