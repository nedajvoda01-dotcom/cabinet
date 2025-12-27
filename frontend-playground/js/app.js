import { renderAuthPage } from "./pages/auth.js";

function getRoute() {
  const h = (window.location.hash || "").replace("#", "").trim();
  if (h === "register") return "register";
  return "login";
}

function ensureHash() {
  if (!window.location.hash) {
    window.location.hash = "#login";
  }
}

function render() {
  const route = getRoute();
  const app = document.getElementById("app");
  if (!app) return;

  if (route === "register") {
    app.innerHTML = renderAuthPage("register");
  } else {
    app.innerHTML = renderAuthPage("login");
  }
}

window.addEventListener("hashchange", render);

ensureHash();
render();
