import { store } from "../store.js";
import { router } from "../router.js";
import { Input } from "../ui/input.js";
import { Button } from "../ui/button.js";

export function renderAuthPage(outlet, { mode }) {
  store.actions.setAuthMode(mode);

  // Чистим outlet (мы рендерим только внутрь layout)
  outlet.innerHTML = "";

  // Карточка
  const card = document.createElement("div");
  card.className = "card";

  // Tabs (пока простые ссылки — позже сделаем ui/tabs.js)
  const tabs = document.createElement("div");
  tabs.className = "auth-tabs";
  tabs.innerHTML = `
    <a href="javascript:void(0)" data-go="login" class="${mode === "login" ? "active" : ""}">Вход</a>
    <a href="javascript:void(0)" data-go="register" class="${mode === "register" ? "active" : ""}">Регистрация</a>
  `;

  // Контент формы
  const form = document.createElement("div");

  // Общие хэндлеры, чтобы корректно снять в unmount
  const cleanups = [];

  const onTabsClick = (e) => {
    const go = e.target?.getAttribute?.("data-go");
    if (go === "login") router.navigate("login");
    if (go === "register") router.navigate("register");
  };
  tabs.addEventListener("click", onTabsClick);
  cleanups.push(() => tabs.removeEventListener("click", onTabsClick));

  // --- UI elements ---
  if (mode === "login") {
    // email
    const emailField = Input({
      value: "ned",
      placeholder: "Почта",
      type: "text",
      onInput: (v) => {
        // как в макете: показываем ошибку, если не "test@test.com"
        const bad = v.trim().length > 0 && v.trim() !== "test@test.com";
        emailField.setError(bad ? "Аккаунт не найден" : "");
      },
    });

    // password (без логики пока)
    const passField = Input({
      value: "",
      placeholder: "Пароль",
      type: "password",
      onInput: () => {},
    });

    // "Забыли пароль?" справа
    const forgotRow = document.createElement("div");
    forgotRow.className = "right";
    forgotRow.innerHTML = `<span class="link">Забыли пароль?</span>`;

    // submit
    const submit = Button({
      text: "Войти",
      variant: "primary",
      fullWidth: true,
      onClick: () => router.navigate("search"),
    });

    // mount to form
    form.appendChild(emailField.el);
    form.appendChild(passField.el);
    form.appendChild(forgotRow);
    form.appendChild(submit.el);

    // cleanup
    cleanups.push(() => emailField.unmount());
    cleanups.push(() => passField.unmount());
    cleanups.push(() => submit.unmount());
  } else {
    const firstName = Input({ placeholder: "Имя" });
    const lastName = Input({ placeholder: "Фамилия" });
    const email = Input({ placeholder: "Почта" });
    const pass = Input({ placeholder: "Пароль", type: "password" });
    const pass2 = Input({ placeholder: "Повторите пароль", type: "password" });

    const submit = Button({
      text: "Зарегистрироваться",
      variant: "primary",
      fullWidth: true,
      onClick: () => router.navigate("search"),
    });

    form.appendChild(firstName.el);
    form.appendChild(lastName.el);
    form.appendChild(email.el);
    form.appendChild(pass.el);
    form.appendChild(pass2.el);
    form.appendChild(submit.el);

    cleanups.push(() => firstName.unmount());
    cleanups.push(() => lastName.unmount());
    cleanups.push(() => email.unmount());
    cleanups.push(() => pass.unmount());
    cleanups.push(() => pass2.unmount());
    cleanups.push(() => submit.unmount());
  }

  // временная ссылка внизу (удобно тестить)
  const toSearch = document.createElement("div");
  toSearch.style.marginTop = "16px";
  toSearch.innerHTML = `<a class="link" href="javascript:void(0)">Перейти в поиск (временно)</a>`;

  const onToSearch = () => router.navigate("search");
  toSearch.addEventListener("click", onToSearch);
  cleanups.push(() => toSearch.removeEventListener("click", onToSearch));

  // Собираем карточку
  card.appendChild(tabs);
  card.appendChild(form);
  card.appendChild(toSearch);

  outlet.appendChild(card);

  return {
    unmount() {
      // снимаем всё, что повесили
      cleanups.forEach((fn) => {
        try { fn(); } catch {}
      });
    },
  };
}
