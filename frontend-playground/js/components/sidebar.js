export function createSidebar({ active = "search", onNavigate } = {}) {
  const root = document.createElement("aside");
  root.className = "sidebar";

  const saved = localStorage.getItem("sidebar_collapsed");
  let collapsed = saved === "1";

  function render() {
    root.dataset.collapsed = collapsed ? "1" : "0";

    root.innerHTML = `
      <div class="sidebar__top">
        <div class="sidebar__brand">${collapsed ? "A" : "AUTOCONTENT.RU"}</div>
      </div>

      <div class="sidebar__section">${collapsed ? "" : "Навигация"}</div>

      <nav class="sidebar__nav">
        ${navItem("search", "🔍", "Поиск автомобиля")}
        ${navItem("content", "🧩", "Контент")}
        ${navItem("autoload", "☁️", "Автовыгрузка")}
      </nav>

      <button class="sidebar__toggle" type="button" title="Свернуть/развернуть">
        ${collapsed ? ">" : "<"}
        ${collapsed ? "" : "<span>Свернуть</span>"}
      </button>
    `;

    // навигация
    root.querySelectorAll("[data-nav]").forEach((el) => {
      el.addEventListener("click", () => {
        const key = el.getAttribute("data-nav");
        if (typeof onNavigate === "function") onNavigate(key);
      });
    });

    // toggle
    root.querySelector(".sidebar__toggle").addEventListener("click", () => {
      collapsed = !collapsed;
      localStorage.setItem("sidebar_collapsed", collapsed ? "1" : "0");
      render();
    });
  }

  function navItem(key, icon, label) {
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

  render();
  return root;
}
