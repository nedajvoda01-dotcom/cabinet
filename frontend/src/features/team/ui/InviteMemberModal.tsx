import React, { useState } from "react";
import { Modal } from "../../shared/ui/Modal";
import { Input } from "../../shared/ui/Input";
import { Button } from "../../shared/ui/Button";
import { teamApi } from "../api";

export function InviteMemberModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const [email, setEmail] = useState("");

  const submit = async () => {
    await teamApi.invite(email);
    onClose();
  };

  return (
    <Modal open={open} onClose={onClose}>
      <div className="space-y-3">
        <h2 className="text-lg font-semibold">Invite member</h2>
        <Input value={email} onChange={(e) => setEmail(e.target.value)} placeholder="email" />
        <Button onClick={submit}>Send invite</Button>
      </div>
    </Modal>
  );
}
