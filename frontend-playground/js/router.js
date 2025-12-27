// router.js — простой, но правильный hash-router
// Поддерживает query: #listing?id=123

function parseHash() {
  const raw = (location.hash || "#login").slice(1); // без #
  const [pathPart, queryPart] = raw.split("?");
  const path = (pathPart || "login").toLowerCase();

  const query = {};
  if (queryPart) {
    const params = new URLSearchParams(queryPart);
    for (const [k, v] of params.entries()) query[k] = v;
  }

  return { path, query, raw: "#" + raw };
}

export const router = {
  getRoute() {
    return parseHash();
  },

  navigate(path, query = {}) {
    const qs = new URLSearchParams(query).toString();
    location.hash = qs ? `#${path}?${qs}` : `#${path}`;
  },

  subscribe(onChange) {
    const handler = () => onChange(parseHash());
    window.addEventListener("hashchange", handler);
    // сразу дергаем, чтобы отрисовать первый раз
    handler();
    return () => window.removeEventListener("hashchange", handler);
  },
};
