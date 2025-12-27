import { createSidebar } from "../components/sidebar.js";

export function renderSearchPage(root) {
  root.innerHTML = "";

  const wrap = document.createElement("div");
  wrap.style.display = "flex";
  wrap.style.minHeight = "100vh";

  const sidebar = createSidebar({
    active: "search",
    onNavigate: (key) => {
      if (key === "search") location.hash = "#search";
      if (key === "content") alert("Контент — следующий экран");
      if (key === "autoload") alert("Автовыгрузка — следующий экран");
    },
  });

  const main = document.createElement("main");
  main.style.flex = "1";
  main.style.padding = "24px";

  main.innerHTML = `
    <div style="background:#fff;border-radius:18px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,.06)">
      <div style="font-weight:800;font-size:20px;margin-bottom:12px">Поиск автомобиля</div>
      <div style="color:#777">Следующий шаг — переносим фильтры + карточки + viewer.</div>
    </div>
  `;

  wrap.appendChild(sidebar);
  wrap.appendChild(main);
  root.appendChild(wrap);
}
