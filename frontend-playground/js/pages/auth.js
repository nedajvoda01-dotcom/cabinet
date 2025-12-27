export function renderAuthPage(root, { mode }) {
  root.innerHTML = `
    <div class="page-center">
      <div class="card">
        <div class="auth-tabs">
          <a href="#login" class="${mode === "login" ? "active" : ""}">Вход</a>
          <a href="#register" class="${mode === "register" ? "active" : ""}">Регистрация</a>
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

          <button class="btn" id="loginBtn">Войти</button>
        `
            : `
          <div class="row"><input class="field" placeholder="Имя"></div>
          <div class="row"><input class="field" placeholder="Фамилия"></div>
          <div class="row"><input class="field" placeholder="Почта"></div>
          <div class="row"><input class="field" placeholder="Пароль" type="password"></div>
          <div class="row"><input class="field" placeholder="Повторите пароль" type="password"></div>

          <button class="btn" id="regBtn">Зарегистрироваться</button>
        `
        }

        <div class="row" style="margin-top:16px">
          <a class="link" href="#search">Перейти в поиск (временно)</a>
        </div>
      </div>
    </div>
  `;

  if (mode === "login") {
    const email = root.querySelector("#email");
    const emailErr = root.querySelector("#emailErr");

    email.addEventListener("input", () => {
      const v = email.value.trim();
      const bad = v.length > 0 && v !== "test@test.com";
      email.classList.toggle("error", bad);
      emailErr.style.display = bad ? "block" : "none";
    });

    root.querySelector("#loginBtn").addEventListener("click", () => {
      location.hash = "#search";
    });
  } else {
    root.querySelector("#regBtn").addEventListener("click", () => {
      location.hash = "#search";
    });
  }
}
