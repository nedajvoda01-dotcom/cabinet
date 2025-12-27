import { router } from "../router.js";

export function renderAuthPage(outlet, { mode }) {
  outlet.innerHTML = "";

  const screen = document.createElement("div");
  screen.className = "auth-screen";

  const card = document.createElement("div");
  card.className = "auth-card";

  const tabs = document.createElement("div");
  tabs.className = "auth-tabs";

  const tabLogin = document.createElement("button");
  tabLogin.type = "button";
  tabLogin.className = "auth-tab";
  tabLogin.textContent = "Вход";

  const tabReg = document.createElement("button");
  tabReg.type = "button";
  tabReg.className = "auth-tab";
  tabReg.textContent = "Регистрация";

  tabs.appendChild(tabLogin);
  tabs.appendChild(tabReg);

  const form = document.createElement("form");
  form.className = "auth-form";
  form.autocomplete = "off";

  card.appendChild(tabs);
  card.appendChild(form);
  screen.appendChild(card);
  outlet.appendChild(screen);

  let currentMode = mode;

  const state = {
    login: { email: "", password: "", emailError: "", passwordError: "" },
    register: { firstName: "", lastName: "", email: "", password: "", password2: "", errors: {} },
  };

  function setActiveTabs() {
    tabLogin.classList.toggle("is-active", currentMode === "login");
    tabReg.classList.toggle("is-active", currentMode === "register");
  }

  function isValidEmail(s) {
    const v = String(s || "").trim();
    return v.includes("@") && v.includes(".") && v.length >= 6;
  }

  function renderField({ placeholder, value, type = "text", error = "", onInput }) {
    const wrap = document.createElement("div");
    wrap.className = "auth-field";

    const input = document.createElement("input");
    input.className = "auth-input";
    input.type = type;
    input.placeholder = placeholder;
    input.value = value;

    if (error) input.classList.add("is-error");

    const err = document.createElement("div");
    if (error) {
      err.className = "auth-error";
      err.textContent = error;
    } else {
      err.className = "auth-spacer";
    }

    input.addEventListener("input", () => onInput(input.value));

    wrap.appendChild(input);
    wrap.appendChild(err);
    return wrap;
  }

  function render() {
    setActiveTabs();
    form.innerHTML = "";

    if (currentMode === "login") {
      const s = state.login;

      form.appendChild(
        renderField({
          placeholder: "Почта",
          value: s.email,
          error: s.emailError,
          onInput: (v) => {
            s.email = v;
            if (s.emailError) s.emailError = "";
            render();
          },
        })
      );

      form.appendChild(
        renderField({
          placeholder: "Пароль",
          type: "password",
          value: s.password,
          error: s.passwordError,
          onInput: (v) => {
            s.password = v;
            if (s.passwordError) s.passwordError = "";
            render();
          },
        })
      );

      const row = document.createElement("div");
      row.className = "auth-row";

      const forgot = document.createElement("span");
      forgot.className = "auth-link";
      forgot.textContent = "Забыли пароль?";
      row.appendChild(forgot);

      const submit = document.createElement("button");
      submit.type = "submit";
      submit.className = "auth-submit";
      submit.textContent = "Войти";

      form.appendChild(row);
      form.appendChild(submit);
      return;
    }

    const s = state.register;

    form.appendChild(
      renderField({
        placeholder: "Имя",
        value: s.firstName,
        error: s.errors.firstName || "",
        onInput: (v) => {
          s.firstName = v;
          delete s.errors.firstName;
          render();
        },
      })
    );

    form.appendChild(
      renderField({
        placeholder: "Фамилия",
        value: s.lastName,
        error: s.errors.lastName || "",
        onInput: (v) => {
          s.lastName = v;
          delete s.errors.lastName;
          render();
        },
      })
    );

    form.appendChild(
      renderField({
        placeholder: "Почта",
        value: s.email,
        error: s.errors.email || "",
        onInput: (v) => {
          s.email = v;
          delete s.errors.email;
          render();
        },
      })
    );

    form.appendChild(
      renderField({
        placeholder: "Пароль",
        type: "password",
        value: s.password,
        error: s.errors.password || "",
        onInput: (v) => {
          s.password = v;
          delete s.errors.password;
          render();
        },
      })
    );

    form.appendChild(
      renderField({
        placeholder: "Повторите пароль",
        type: "password",
        value: s.password2,
        error: s.errors.password2 || "",
        onInput: (v) => {
          s.password2 = v;
          delete s.errors.password2;
          render();
        },
      })
    );

    const submit = document.createElement("button");
    submit.type = "submit";
    submit.className = "auth-submit";
    submit.textContent = "Зарегистрироваться";
    form.appendChild(submit);
  }

  tabs.addEventListener("click", (e) => {
    if (e.target === tabLogin) router.navigate("login");
    if (e.target === tabReg) router.navigate("register");
  });

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    if (currentMode === "login") {
      const s = state.login;
      s.emailError = "";
      s.passwordError = "";

      const email = s.email.trim();
      const pass = s.password;

      if (!email) s.emailError = "Введите почту";
      else if (!isValidEmail(email)) s.emailError = "Некорректная почта";
      if (!pass) s.passwordError = "Введите пароль";

      render();
      if (!s.emailError && !s.passwordError) {
        alert("Вход (заглушка).");
      }
      return;
    }

    const s = state.register;
    s.errors = {};

    if (!s.firstName.trim()) s.errors.firstName = "Введите имя";
    if (!s.lastName.trim()) s.errors.lastName = "Введите фамилию";

    const email = s.email.trim();
    if (!email) s.errors.email = "Введите почту";
    else if (!isValidEmail(email)) s.errors.email = "Некорректная почта";

    if (!s.password) s.errors.password = "Введите пароль";
    if (!s.password2) s.errors.password2 = "Повторите пароль";
    if (s.password && s.password2 && s.password !== s.password2) s.errors.password2 = "Пароли не совпадают";

    render();
    if (Object.keys(s.errors).length === 0) {
      alert("Регистрация (заглушка).");
    }
  });

  // initial
  render();

  return {
    unmount() {},
  };
}
