export function Input({
  value = "",
  placeholder = "",
  type = "text",
  error = "", // текст ошибки (или пусто)
  onInput,
  name = "",
  autocomplete = "",
} = {}) {
  const wrap = document.createElement("div");
  wrap.className = "ui-field";

  const input = document.createElement("input");
  input.className = "ui-input";
  input.type = type;
  input.value = value;
  input.placeholder = placeholder;
  if (name) input.name = name;
  if (autocomplete) input.autocomplete = autocomplete;

  const err = document.createElement("div");
  err.className = "ui-error";
  err.style.display = error ? "block" : "none";
  err.textContent = error || "";

  const handler = () => {
    if (typeof onInput === "function") onInput(input.value);
  };
  input.addEventListener("input", handler);

  wrap.appendChild(input);
  wrap.appendChild(err);

  return {
    el: wrap,
    input,
    setError(message) {
      err.textContent = message || "";
      err.style.display = message ? "block" : "none";
      input.classList.toggle("ui-input--error", !!message);
    },
    unmount() {
      input.removeEventListener("input", handler);
    },
  };
}
