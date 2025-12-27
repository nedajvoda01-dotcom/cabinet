import type { NavItem } from "../../../shared/types/ui";

export const workNav: NavItem[] = [
  { label: "Cards", to: "/work", permission: "pipeline.view" },
  { label: "Parser", to: "/work/parser", permission: "pipeline.view" },
  { label: "Photos", to: "/work/photos", permission: "pipeline.view" },
  { label: "Export", to: "/work/export", permission: "pipeline.view" },
  { label: "Publish", to: "/work/publish", permission: "pipeline.view" },
];
