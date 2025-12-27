import { store } from "../store.js";
import { router } from "../router.js";

export function renderAuthPage(outlet, { mode }) {
  // синхронизируем mode со store (на будущее)
  store.actions.setAuthMode(mode);

  outlet.innerHTML = `
    <div class="card">
      <div class="auth-tabs">
        <a href="javascript:void(0)" data-go="login" class="${mode === "login" ? "active" : ""}">Вход</a>
        <a href="javascript:void(0)" data-go="register" class="${mode === "register" ? "active" : ""}">Регистрация</a>
      </div>

      ${
        mode === "login"
          ? `
        <div class="row">
          <input id="email" class="field" placeholder="Почта" value="ned">
          <div id="emailErr" class="err" style="display:none;">Аккаунт не найден</div>
        </div>

        <div class="row">
          <input id="pass" class="field" placeholder="Пароль" type="password">
          <div class="right"><span class="link">Забыли пароль?</span></div>
        </div>

        <button class="btn" id="submitBtn">Войти</button>
      `
          : `
        <div class="row"><input class="field" placeholder="Имя"></div>
        <div class="row"><input class="field" placeholder="Фамилия"></div>
        <div class="row"><input class="field" placeholder="Почта"></div>
        <div class="row"><input class="field" placeholder="Пароль" type="password"></div>
        <div class="row"><input class="field" placeholder="Повторите пароль" type="password"></div>

        <button class="btn" id="submitBtn">Зарегистрироваться</button>
      `
      }

      <div class="row" style="margin-top:16px">
        <a class="link" href="javascript:void(0)" id="toSearch">Перейти в поиск (временно)</a>
      </div>
    </div>
  `;

  const onClickTabs = (e) => {
    const go = e.target?.getAttribute?.("data-go");
    if (go === "login") router.navigate("login");
    if (go === "register") router.navigate("register");
  };

  outlet.querySelector(".auth-tabs").addEventListener("click", onClickTabs);

  // логика ошибки для login
  let onEmailInput = null;
  if (mode === "login") {
    const email = outlet.querySelector("#email");
    const emailErr = outlet.querySelector("#emailErr");

    onEmailInput = () => {
      const v = email.value.trim();
      const bad = v.length > 0 && v !== "test@test.com";
      email.classList.toggle("error", bad);
      emailErr.style.display = bad ? "block" : "none";
    };

    email.addEventListener("input", onEmailInput);
  }

  const toSearch = () => router.navigate("search");
  outlet.querySelector("#toSearch").addEventListener("click", toSearch);
  outlet.querySelector("#submitBtn").addEventListener("click", toSearch);

  return {
    unmount() {
      outlet.querySelector(".auth-tabs")?.removeEventListener?.("click", onClickTabs);
      if (mode === "login") {
        const email = outlet.querySelector("#email");
        if (email && onEmailInput) email.removeEventListener("input", onEmailInput);
      }
      outlet.querySelector("#toSearch")?.removeEventListener?.("click", toSearch);
      outlet.querySelector("#submitBtn")?.removeEventListener?.("click", toSearch);
    },
  };
}
