export function initRouter(routes, defaultHash) {
  function resolve() {
    const hash = (location.hash || defaultHash).toLowerCase();
    const handler = routes[hash] || routes[defaultHash];
    handler();
  }

  window.addEventListener("hashchange", resolve);
  resolve();
}
