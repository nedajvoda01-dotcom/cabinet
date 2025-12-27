export function Tabs({
  items = [], // [{ key, label }]
  value = "", // активный key
  onChange,
} = {}) {
  const root = document.createElement("div");
  root.className = "ui-tabs";

  function render() {
    root.innerHTML = items
      .map((it) => {
        const active = it.key === value;
        return `
          <button type="button"
            class="ui-tabs__tab ${active ? "is-active" : ""}"
            data-key="${it.key}">
            ${it.label}
          </button>
        `;
      })
      .join("");
  }

  const onClick = (e) => {
    const btn = e.target.closest?.("[data-key]");
    if (!btn) return;
    const key = btn.getAttribute("data-key");
    if (!key || key === value) return;
    if (typeof onChange === "function") onChange(key);
  };

  root.addEventListener("click", onClick);
  render();

  return {
    el: root,
    setValue(next) {
      value = next;
      render();
    },
    unmount() {
      root.removeEventListener("click", onClick);
    },
  };
}
