function tabsHtml(active) {
  const isLogin = active === "login";
  const isReg = active === "register";

  return `
    <div class="auth-tabs">
      <a class="auth-tab ${isLogin ? "is-active" : ""}" href="#login">Вход</a>
      <a class="auth-tab ${isReg ? "is-active" : ""}" href="#register">Регистрация</a>
    </div>
  `;
}

function loginHtml() {
  return `
    <div class="auth-card">
      ${tabsHtml("login")}

      <form class="auth-form" id="loginForm" autocomplete="on">
        <div>
          <input class="auth-input" id="loginEmail" type="text" placeholder="" value="ned" />
          <div class="auth-error" id="loginEmailError" style="display:none;">Аккаунт не найден</div>
        </div>

        <input class="auth-input" id="loginPassword" type="password" placeholder="Пароль" />

        <div class="auth-forgot">
          <a href="#login" id="forgotLink">Забыли пароль?</a>
        </div>

        <button class="auth-submit" type="submit">Войти</button>
      </form>
    </div>
  `;
}

function registerHtml() {
  return `
    <div class="auth-card">
      ${tabsHtml("register")}

      <form class="auth-form" id="registerForm" autocomplete="on">
        <input class="auth-input" id="regFirstName" type="text" placeholder="Имя" />
        <input class="auth-input" id="regLastName" type="text" placeholder="Фамилия" />
        <input class="auth-input" id="regEmail" type="text" placeholder="Почта" />
        <input class="auth-input" id="regPassword" type="password" placeholder="Пароль" />
        <input class="auth-input" id="regPassword2" type="password" placeholder="Повторите пароль" />

        <button class="auth-submit" type="submit">Зарегистрироваться</button>
      </form>
    </div>
  `;
}

/**
 * Единая точка рендера страницы авторизации
 * @param {"login"|"register"} mode
 */
export function renderAuthPage(mode) {
  const html = mode === "register" ? registerHtml() : loginHtml();

  // бинды делаем после того как html вставился в DOM
  queueMicrotask(() => {
    if (mode === "login") bindLogin();
    else bindRegister();
  });

  return html;
}

function bindLogin() {
  const form = document.getElementById("loginForm");
  const email = document.getElementById("loginEmail");
  const pass = document.getElementById("loginPassword");
  const err = document.getElementById("loginEmailError");

  if (!form || !email || !pass || !err) return;

  // демо-валидация: если в email меньше 4 символов — считаем "не найден"
  function validateEmailDemo() {
    const v = String(email.value || "").trim();
    const show = v.length > 0 && v.length < 4;

    email.classList.toggle("is-error", show);
    err.style.display = show ? "block" : "none";
  }

  email.addEventListener("input", validateEmailDemo);
  validateEmailDemo();

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    // пока без “излишеств”: просто проверим, что что-то введено
    const emailV = String(email.value || "").trim();
    const passV = String(pass.value || "").trim();

    if (!emailV || !passV) return;

    // временно: успешный вход перекинет на поиск (позже заменим)
    window.location.hash = "#login";
  });
}

function bindRegister() {
  const form = document.getElementById("registerForm");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    // пока просто “есть форма и кнопка”
    window.location.hash = "#login";
  });
}
