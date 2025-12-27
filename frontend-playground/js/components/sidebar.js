import { store } from "../store.js";
import { router } from "../router.js";

export function createSidebar({ active = "search" } = {}) {
  const root = document.createElement("aside");
  root.className = "sidebar";

  function render() {
    const s = store.getState();
    const collapsed = s.ui.sidebarCollapsed;

    root.dataset.collapsed = collapsed ? "1" : "0";

    root.innerHTML = `
      <div class="sidebar__top">
        <div class="sidebar__brand">${collapsed ? "A" : "AUTOCONTENT.RU"}</div>
      </div>

      <div class="sidebar__section">${collapsed ? "" : "Навигация"}</div>

      <nav class="sidebar__nav">
        ${item("search", "🔍", "Поиск автомобиля", active, collapsed)}
        ${item("content", "🧩", "Контент", active, collapsed)}
        ${item("autoload", "☁️", "Автовыгрузка", active, collapsed)}
      </nav>

      <button class="sidebar__toggle" type="button" title="Свернуть/развернуть">
        ${collapsed ? ">" : "<"}
        ${collapsed ? "" : "<span>Свернуть</span>"}
      </button>
    `;

    // navigation
    root.querySelectorAll("[data-nav]").forEach((el) => {
      el.addEventListener("click", () => {
        const key = el.getAttribute("data-nav");
        if (key === "search") router.navigate("search");
        if (key === "content") router.navigate("content");
        if (key === "autoload") router.navigate("autoload");
      });
    });

    // toggle
    root.querySelector(".sidebar__toggle").addEventListener("click", () => {
      store.actions.setSidebarCollapsed(!collapsed);
    });
  }

  const unsub = store.subscribe((st, meta) => {
    if (meta?.type === "ui/sidebar") render();
  });

  render();

  return root;
}

function item(key, icon, label, active, collapsed) {
  const isActive = key === active;
  return `
    <a class="sidebar__item ${isActive ? "is-active" : ""}"
       data-nav="${key}"
       href="javascript:void(0)"
       title="${collapsed ? label : ""}">
      <span class="sidebar__icon">${icon}</span>
      ${collapsed ? "" : `<span class="sidebar__label">${label}</span>`}
    </a>
  `;
}
