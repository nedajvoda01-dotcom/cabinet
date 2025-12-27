const app = document.getElementById("app");

const state = {
  mode: "login", // login | register
  login: { email: "", password: "", emailError: "" },
  register: { firstName: "", lastName: "", email: "", password: "", password2: "", emailError: "" },
};

function getModeFromHash() {
  const h = (location.hash || "").toLowerCase();
  if (h === "#register") return "register";
  return "login"; // default
}

function setHashForMode(mode) {
  const next = mode === "register" ? "#register" : "#login";
  if (location.hash !== next) location.hash = next;
}

function setMode(mode, { syncHash = true } = {}) {
  state.mode = mode;

  // сброс ошибок при переключении
  state.login.emailError = "";
  state.register.emailError = "";

  if (syncHash) setHashForMode(mode);
  render();
}

function render() {
  app.innerHTML = `
    <main class="auth-page">
      <section class="auth-card">
        <div class="auth-inner">
          ${renderTabs()}
          ${state.mode === "login" ? renderLogin() : renderRegister()}
        </div>
      </section>
    </main>
  `;

  bindTabs();
  if (state.mode === "login") bindLogin();
  else bindRegister();
}

function renderTabs() {
  const loginActive = state.mode === "login";
  return `
    <ul class="auth-tabs">
      <li><span class="auth-tab ${loginActive ? "is-active" : ""}" data-tab="login">Вход</span></li>
      <li><span class="auth-tab ${!loginActive ? "is-active" : ""}" data-tab="register">Регистрация</span></li>
    </ul>
  `;
}

/* ---------------- LOGIN ---------------- */

function renderLogin() {
  const s = state.login;
  const hasErr = Boolean(s.emailError);

  return `
    <form class="auth-form" id="login-form" autocomplete="off" novalidate>
      <div class="field ${hasErr ? "has-error" : ""}" id="login-email-field">
        <input
          class="input ${hasErr ? "is-error" : ""}"
          type="text"
          name="email"
          placeholder="Почта"
          value="${esc(s.email)}"
        />
        ${hasErr ? `<div class="field-error">${esc(s.emailError)}</div>` : ``}
      </div>

      <div class="field">
        <input
          class="input"
          type="password"
          name="password"
          placeholder="Пароль"
          value="${esc(s.password)}"
        />
      </div>

      <div class="forgot-row">
        <span class="forgot-link" id="forgot-link">Забыли пароль?</span>
      </div>

      <button class="auth-btn" type="submit">Войти</button>
    </form>
  `;
}

function bindLogin() {
  const form = app.querySelector("#login-form");
  const email = app.querySelector('input[name="email"]');
  const password = app.querySelector('input[name="password"]');
  const forgot = app.querySelector("#forgot-link");

  email.addEventListener("input", (e) => {
    state.login.email = e.target.value;
    if (state.login.emailError) {
      state.login.emailError = "";
      render();
    }
  });

  password.addEventListener("input", (e) => {
    state.login.password = e.target.value;
  });

  forgot.addEventListener("click", () => {
    alert("Забыли пароль? (заглушка)");
  });

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const emailVal = (state.login.email || "").trim().toLowerCase();

    // Заглушка поведения: если ввели что-то и оно не demo — “Аккаунт не найден”
    if (emailVal && emailVal !== "test@test.ru") {
      state.login.emailError = "Аккаунт не найден";
      render();
      return;
    }

    alert("Вход (заглушка).");
  });
}

/* ---------------- REGISTER ---------------- */

function renderRegister() {
  const s = state.register;
  const hasErr = Boolean(s.emailError);

  return `
    <form class="auth-form" id="register-form" autocomplete="off" novalidate>
      <div class="field">
        <input class="input" type="text" name="firstName" placeholder="Имя" value="${esc(s.firstName)}" />
      </div>

      <div class="field">
        <input class="input" type="text" name="lastName" placeholder="Фамилия" value="${esc(s.lastName)}" />
      </div>

      <div class="field ${hasErr ? "has-error" : ""}">
        <input
          class="input ${hasErr ? "is-error" : ""}"
          type="text"
          name="email"
          placeholder="Почта"
          value="${esc(s.email)}"
        />
        ${hasErr ? `<div class="field-error">${esc(s.emailError)}</div>` : ``}
      </div>

      <div class="field">
        <input class="input" type="password" name="password" placeholder="Пароль" value="${esc(s.password)}" />
      </div>

      <div class="field">
        <input class="input" type="password" name="password2" placeholder="Повторите пароль" value="${esc(s.password2)}" />
      </div>

      <button class="auth-btn" type="submit">Зарегистрироваться</button>
    </form>
  `;
}

function bindRegister() {
  const form = app.querySelector("#register-form");

  const firstName = app.querySelector('input[name="firstName"]');
  const lastName = app.querySelector('input[name="lastName"]');
  const email = app.querySelector('input[name="email"]');
  const password = app.querySelector('input[name="password"]');
  const password2 = app.querySelector('input[name="password2"]');

  firstName.addEventListener("input", (e) => (state.register.firstName = e.target.value));
  lastName.addEventListener("input", (e) => (state.register.lastName = e.target.value));

  email.addEventListener("input", (e) => {
    state.register.email = e.target.value;
    if (state.register.emailError) {
      state.register.emailError = "";
      render();
    }
  });

  password.addEventListener("input", (e) => (state.register.password = e.target.value));
  password2.addEventListener("input", (e) => (state.register.password2 = e.target.value));

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const em = (state.register.email || "").trim();
    const emOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em);

    if (em && !emOk) {
      state.register.emailError = "Некорректная почта";
      render();
      return;
    }

    if (state.register.password && state.register.password2 && state.register.password !== state.register.password2) {
      state.register.emailError = "Пароли не совпадают";
      render();
      return;
    }

    alert("Регистрация (заглушка).");
  });
}

/* ---------------- Tabs binding ---------------- */

function bindTabs() {
  app.querySelectorAll(".auth-tab").forEach((el) => {
    el.addEventListener("click", () => setMode(el.dataset.tab, { syncHash: true }));
  });
}

/* ---------------- helpers ---------------- */

function esc(v) {
  return String(v ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

/* init: read hash + listen */
state.mode = getModeFromHash();
window.addEventListener("hashchange", () => {
  const next = getModeFromHash();
  if (next !== state.mode) setMode(next, { syncHash: false });
});

if (!location.hash) setHashForMode(state.mode);
render();
