export function createAuthLayout() {
  const root = document.createElement("div");
  root.className = "layout-auth";

  const outlet = document.createElement("div");
  outlet.className = "layout-auth__outlet";

  root.appendChild(outlet);

  return {
    el: root,
    outlet,
    unmount() {},
  };
}
