export function Button({
  text = "Button",
  variant = "primary", // primary | ghost | link
  fullWidth = false,
  size = "md", // md | sm
  onClick,
  type = "button",
  disabled = false,
  title = "",
} = {}) {
  const el = document.createElement("button");
  el.type = type;
  el.className = [
    "ui-btn",
    `ui-btn--${variant}`,
    `ui-btn--${size}`,
    fullWidth ? "ui-btn--full" : "",
  ]
    .filter(Boolean)
    .join(" ");

  el.textContent = text;
  if (title) el.title = title;
  if (disabled) el.disabled = true;

  const handler = (e) => {
    if (disabled) return;
    if (typeof onClick === "function") onClick(e);
  };

  el.addEventListener("click", handler);

  return {
    el,
    unmount() {
      el.removeEventListener("click", handler);
    },
  };
}
