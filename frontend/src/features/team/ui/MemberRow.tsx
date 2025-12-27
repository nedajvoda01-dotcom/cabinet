import React from "react";
import type { Member } from "../model";

export function MemberRow({ member }: { member: Member }) {
  return <div>{member.email}</div>;
}
