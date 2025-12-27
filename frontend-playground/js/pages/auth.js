import { store } from "../store.js";
import { router } from "../router.js";

export function renderAuthPage(outlet, { mode }) {
  store.actions.setAuthMode(mode);

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

  // local states
  let currentMode = mode;

  const loginState = {
    email: "",
    password: "",
    emailError: "", // "Аккаунт не найден"
    passwordError: "",
  };

  const regState = {
    firstName: "",
    lastName: "",
    email: "",
    password: "",
    password2: "",
    errors: {},
  };

  // helpers
  function setActiveTabs() {
    tabLogin.classList.toggle("is-active", currentMode === "login");
    tabReg.classList.toggle("is-active", currentMode === "register");
  }

  function isValidEmail(s) {
    const v = String(s || "").trim();
    return v.includes("@") && v.includes(".") && v.length >= 6;
  }

  function field({ placeholder, value, type = "text", error = "", onInput }) {
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

    const onInputHandler = () => onInput(input.value);
    input.addEventListener("input", onInputHandler);

    wrap.appendChild(input);
    wrap.appendChild(err);

    return {
      wrap,
      input,
      unmount() {
        input.removeEventListener("input", onInputHandler);
      },
    };
  }

  // render
  let mounted = [];

  function clearMounted() {
    mounted.forEach((m) => {
      try { m.unmount?.(); } catch {}
    });
    mounted = [];
    form.innerHTML = "";
  }

  function render() {
    setActiveTabs();
    clearMounted();

    if (currentMode === "login") {
      const email = field({
        placeholder: "Почта",
        value: loginState.email,
        type: "text",
        error: loginState.emailError,
        onInput: (v) => {
          loginState.email = v;
          if (loginState.emailError) {
            loginState.emailError = "";
            render();
          }
        },
      });

      const pass = field({
        placeholder: "Пароль",
        value: loginState.password,
        type: "password",
        error: loginState.passwordError,
        onInput: (v) => {
          loginState.password = v;
          if (loginState.passwordError) {
            loginState.passwordError = "";
            render();
          }
        },
      });

      const forgotRow = document.createElement("div");
      forgotRow.className = "auth-row";

      const forgot = document.createElement("span");
      forgot.className = "auth-link";
      forgot.textContent = "Забыли пароль?";
      forgotRow.appendChild(forgot);

      const submit = document.createElement("button");
      submit.type = "submit";
      submit.className = "auth-submit";
      submit.textContent = "Войти";

      form.appendChild(email.wrap);
      form.appendChild(pass.wrap);
      form.appendChild(forgotRow);
      form.appendChild(submit);

      mounted.push(email, pass);
      return;
    }

    // register
    const firstName = field({
      placeholder: "Имя",
      value: regState.firstName,
      error: regState.errors.firstName || "",
      onInput: (v) => {
        regState.firstName = v;
        if (regState.errors.firstName) {
          delete regState.errors.firstName;
          render();
        }
      },
    });

    const lastName = field({
      placeholder: "Фамилия",
      value: regState.lastName,
      error: regState.errors.lastName || "",
      onInput: (v) => {
        regState.lastName = v;
        if (regState.errors.lastName) {
          delete regState.errors.lastName;
          render();
        }
      },
    });

    const email = field({
      placeholder: "Почта",
      value: regState.email,
      error: regState.errors.email || "",
      onInput: (v) => {
        regState.email = v;
        if (regState.errors.email) {
          delete regState.errors.email;
          render();
        }
      },
    });

    const pass = field({
      placeholder: "Пароль",
      value: regState.password,
      type: "password",
      error: regState.errors.password || "",
      onInput: (v) => {
        regState.password = v;
        if (regState.errors.password) {
          delete regState.errors.password;
          render();
        }
      },
    });

    const pass2 = field({
      placeholder: "Повторите пароль",
      value: regState.password2,
      type: "password",
      error: regState.errors.password2 || "",
      onInput: (v) => {
        regState.password2 = v;
        if (regState.errors.password2) {
          delete regState.errors.password2;
          render();
        }
      },
    });

    const submit = document.createElement("button");
    submit.type = "submit";
    submit.className = "auth-submit";
    submit.textContent = "Зарегистрироваться";

    form.appendChild(firstName.wrap);
    form.appendChild(lastName.wrap);
    form.appendChild(email.wrap);
    form.appendChild(pass.wrap);
    form.appendChild(pass2.wrap);
    form.appendChild(submit);

    mounted.push(firstName, lastName, email, pass, pass2);
  }

  // events
  const onTabs = (e) => {
    if (e.target === tabLogin) router.navigate("login");
    if (e.target === tabReg) router.navigate("register");
  };
  tabs.addEventListener("click", onTabs);

  const onSubmit = (e) => {
    e.preventDefault();

    if (currentMode === "login") {
      loginState.emailError = "";
      loginState.passwordError = "";

      const email = loginState.email.trim();
      const pass = loginState.password;

      if (!email) loginState.emailError = "Введите почту";
      else if (!isValidEmail(email)) loginState.emailError = "Некорректная почта";

      if (!pass) loginState.passwordError = "Введите пароль";

      // без бэка: просто показываем заглушку.
      // При желании можно имитировать "аккаунт не найден" по условию.
      if (!loginState.emailError && !loginState.passwordError) {
        alert("Вход (заглушка). Пока без бэка.");
        router.navigate("search");
      }

      render();
      return;
    }

    // register validation (минимально)
    regState.errors = {};
    const fn = regState.firstName.trim();
    const ln = regState.lastName.trim();
    const email = regState.email.trim();
    const p1 = regState.password;
    const p2 = regState.password2;

    if (!fn) regState.errors.firstName = "Введите имя";
    if (!ln) regState.errors.lastName = "Введите фамилию";

    if (!email) regState.errors.email = "Введите почту";
    else if (!isValidEmail(email)) regState.errors.email = "Некорректная почта";

    if (!p1) regState.errors.password = "Введите пароль";
    if (!p2) regState.errors.password2 = "Повторите пароль";
    if (p1 && p2 && p1 !== p2) regState.errors.password2 = "Пароли не совпадают";

    if (Object.keys(regState.errors).length === 0) {
      alert("Регистрация (заглушка). Пока без бэка.");
      router.navigate("login");
    }

    render();
  };
  form.addEventListener("submit", onSubmit);

  // initial
  render();

  return {
    unmount() {
      try { tabs.removeEventListener("click", onTabs); } catch {}
      try { form.removeEventListener("submit", onSubmit); } catch {}
      clearMounted();
    },
  };
}
