import { initRouter } from "./router.js";
import { renderAuthPage } from "./pages/auth.js";
import { renderSearchPage } from "./pages/search.js";

const app = document.getElementById("app");

const routes = {
  "#login": () => renderAuthPage(app, { mode: "login" }),
  "#register": () => renderAuthPage(app, { mode: "register" }),
  "#search": () => renderSearchPage(app),
};

initRouter(routes, "#login");
