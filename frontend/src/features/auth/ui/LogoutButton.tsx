import React from "react";
import { Button } from "../../shared/ui/Button";
import { useAuth } from "./AuthProvider";

export function LogoutButton() {
  const { logout } = useAuth();
  return (
    <Button type="button" variant="ghost" onClick={() => logout()}>
      Logout
    </Button>
  );
}
