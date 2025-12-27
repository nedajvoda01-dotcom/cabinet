import { store } from "../store.js";

export function PhotoViewer() {
  const root = document.createElement("div");
  root.className = "viewer";
  root.style.display = "none";

  let unsub = null;

  const onKeyDown = (e) => {
    const s = store.getState();
    if (!s.ui.viewer.open) return;

    if (e.key === "Escape") store.actions.closeViewer();
    if (e.key === "ArrowRight") store.actions.setViewerIndex(s.ui.viewer.index + 1);
    if (e.key === "ArrowLeft") store.actions.setViewerIndex(s.ui.viewer.index - 1);
  };

  function render() {
    const s = store.getState();
    const v = s.ui.viewer;

    if (!v.open) {
      root.style.display = "none";
      document.body.style.overflow = "";
      return;
    }

    root.style.display = "block";
    document.body.style.overflow = "hidden";

    const images = Array.isArray(v.images) ? v.images : [];
    const idx = Math.max(0, Math.min(v.index || 0, Math.max(0, images.length - 1)));
    const active = images[idx] || "";

    root.innerHTML = `
      <div class="viewer__backdrop" data-action="close"></div>

      <div class="viewer__panel">
        <button class="viewer__close" type="button" data-action="close" title="Закрыть">✕</button>

        <div class="viewer__body">
          <div class="viewer__thumbs">
            ${images
              .map((src, i) => {
                const activeClass = i === idx ? "is-active" : "";
                return `
                  <button class="viewer__thumb ${activeClass}" type="button" data-action="thumb" data-index="${i}">
                    <img src="${src}" alt="">
                  </button>
                `;
              })
              .join("")}
          </div>

          <div class="viewer__main">
            <button class="viewer__nav viewer__nav--prev" type="button" data-action="prev" title="Назад">‹</button>

            <div class="viewer__image">
              ${active ? `<img src="${active}" alt="">` : `<div class="viewer__empty">Нет фото</div>`}
            </div>

            <button class="viewer__nav viewer__nav--next" type="button" data-action="next" title="Вперёд">›</button>
          </div>
        </div>

        <div class="viewer__footer">
          <div class="viewer__counter">${images.length ? idx + 1 : 0} / ${images.length}</div>
        </div>
      </div>
    `;
  }

  const onClick = (e) => {
    const t = e.target.closest?.("[data-action]");
    if (!t) return;

    const action = t.getAttribute("data-action");
    const s = store.getState();
    const v = s.ui.viewer;
    const max = Math.max(0, (v.images?.length || 0) - 1);

    if (action === "close") {
      store.actions.closeViewer();
      return;
    }

    if (action === "prev") {
      store.actions.setViewerIndex((v.index || 0) - 1);
      return;
    }

    if (action === "next") {
      store.actions.setViewerIndex((v.index || 0) + 1);
      return;
    }

    if (action === "thumb") {
      const i = Number(t.getAttribute("data-index"));
      if (!Number.isNaN(i)) store.actions.setViewerIndex(i);
      return;
    }

    // safety clamp (на всякий)
    store.actions.setViewerIndex(Math.min(Math.max(0, v.index || 0), max));
  };

  root.addEventListener("click", onClick);
  window.addEventListener("keydown", onKeyDown);

  unsub = store.subscribe((st, meta) => {
    if (
      meta?.type === "ui/viewer/open" ||
      meta?.type === "ui/viewer/close" ||
      meta?.type === "ui/viewer/index"
    ) {
      render();
    }
  });

  // initial render
  render();

  return {
    el: root,
    unmount() {
      try { root.removeEventListener("click", onClick); } catch {}
      try { window.removeEventListener("keydown", onKeyDown); } catch {}
      try { unsub?.(); } catch {}
      document.body.style.overflow = "";
    },
  };
}
