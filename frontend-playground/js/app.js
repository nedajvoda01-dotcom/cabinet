import { router } from "./router.js";
import { store } from "./store.js";

import { createAuthLayout } from "./layouts/auth-layout.js";
import { createAppLayout } from "./layouts/app-layout.js";

import { renderAuthPage } from "./pages/auth.js";
import { renderSearchPage } from "./pages/search.js";

// временные заглушки будущих страниц
function renderStubPage(outlet, title) {
  outlet.innerHTML = `
    <div style="background:#fff;border-radius:18px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,.06)">
      <div style="font-weight:800;font-size:20px;margin-bottom:8px">${title}</div>
      <div style="color:#777">Заглушка страницы</div>
    </div>
  `;
  return { unmount() {} };
}

const appRoot = document.getElementById("app");

let currentLayout = null;
let currentPage = null;

function unmountCurrent() {
  try { currentPage?.unmount?.(); } catch {}
  try { currentLayout?.unmount?.(); } catch {}
  currentPage = null;
  currentLayout = null;
  appRoot.innerHTML = "";
}

function mount(layout, pageMountFn) {
  appRoot.innerHTML = "";
  appRoot.appendChild(layout.el);
  currentLayout = layout;
  currentPage = pageMountFn(layout.outlet, layout);
}

function routeToUI(route) {
  // auth routes
  if (route.path === "login" || route.path === "register") {
    const layout = createAuthLayout();
    mount(layout, (outlet) => renderAuthPage(outlet, { mode: route.path }));
    return;
  }

  // app routes
  const activeNav =
    route.path === "search" ? "search" :
    route.path === "content" ? "content" :
    route.path === "autoload" ? "autoload" :
    "search";

  const layout = createAppLayout({ activeNav });

  mount(layout, (outlet) => {
    if (route.path === "search") return renderSearchPage(outlet);
    if (route.path === "content") return renderStubPage(outlet, "Контент");
    if (route.path === "autoload") return renderStubPage(outlet, "Автовыгрузка");
    return renderStubPage(outlet, "Страница");
  });
}

router.subscribe((route) => {
  // при смене маршрута полностью пересобираем дерево (чётко и предсказуемо)
  unmountCurrent();
  routeToUI(route);
});

// если hash пустой — отправляем на login
if (!location.hash) router.navigate("login");
