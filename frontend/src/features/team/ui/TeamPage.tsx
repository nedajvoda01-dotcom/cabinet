import React from "react";
import { teamApi } from "../api";
import type { Member } from "../model";
import { Table } from "../../shared/ui/Table";
import { InviteMemberModal } from "./InviteMemberModal";
import { MemberRow } from "./MemberRow";

export function TeamPage() {
  const [items, setItems] = React.useState<Member[]>([]);
  const [showInvite, setShowInvite] = React.useState(false);

  React.useEffect(() => {
    teamApi.list().then(setItems);
  }, []);

  return (
    <div className="p-4 space-y-3">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">Team</h1>
        <button className="text-sm" onClick={() => setShowInvite(true)}>
          Invite member
        </button>
      </div>
      <Table headers={["Email", "Role"]} rows={items.map((m) => [<MemberRow key={m.id} member={m} />, m.role])} />
      <InviteMemberModal open={showInvite} onClose={() => setShowInvite(false)} />
    </div>
  );
}
