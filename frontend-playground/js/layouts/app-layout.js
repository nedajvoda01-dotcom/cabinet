import { createSidebar } from "../components/sidebar.js";

export function createAppLayout({ activeNav = "search" } = {}) {
  const root = document.createElement("div");
  root.className = "layout-app";

  const sidebarHost = document.createElement("div");
  sidebarHost.className = "layout-app__sidebar";

  const main = document.createElement("main");
  main.className = "layout-app__main";

  const overlay = document.createElement("div");
  overlay.className = "layout-app__overlay";
  overlay.id = "overlay-root";

  // sidebar создадим как компонент
  const sidebar = createSidebar({ active: activeNav });

  sidebarHost.appendChild(sidebar);
  root.appendChild(sidebarHost);
  root.appendChild(main);
  root.appendChild(overlay);

  return {
    el: root,
    outlet: main,
    overlay,
    unmount() {},
  };
}
