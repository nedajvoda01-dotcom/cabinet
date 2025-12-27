const STORAGE_KEY = "fp_state_v1";

const defaultState = {
  ui: {
    sidebarCollapsed: false,
    viewer: { open: false, images: [], index: 0 },
  },
  auth: {
    mode: "login", // "login" | "register"
  },
  search: {
    filters: {
      status: "active",      // "active" | "archived"
      condition: "all",      // "all" | "used" | "new"
      seller: "all",         // "all" | "owner" | "dealer"
      brand: "",
      model: "",
      generation: "",
      color: "",
      region: "",
    },
  },
};

function loadPersisted() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return {};
    return JSON.parse(raw);
  } catch {
    return {};
  }
}

function savePersisted(state) {
  // сохраняем только то, что надо помнить между перезагрузками
  const persisted = {
    ui: { sidebarCollapsed: state.ui.sidebarCollapsed },
  };
  localStorage.setItem(STORAGE_KEY, JSON.stringify(persisted));
}

function deepMerge(base, patch) {
  const out = Array.isArray(base) ? [...base] : { ...base };
  for (const k in patch) {
    const pv = patch[k];
    const bv = base?.[k];
    if (pv && typeof pv === "object" && !Array.isArray(pv)) {
      out[k] = deepMerge(bv || {}, pv);
    } else {
      out[k] = pv;
    }
  }
  return out;
}

let state = deepMerge(defaultState, loadPersisted());
const listeners = new Set();

export const store = {
  getState() {
    return state;
  },

  setState(patch, meta = {}) {
    const next = deepMerge(state, patch);
    state = next;
    savePersisted(state);
    listeners.forEach((fn) => fn(state, meta));
  },

  subscribe(fn) {
    listeners.add(fn);
    return () => listeners.delete(fn);
  },

  // удобные экшены (чтобы не размазывать логику по коду)
  actions: {
    setSidebarCollapsed(value) {
      store.setState({ ui: { sidebarCollapsed: !!value } }, { type: "ui/sidebar" });
    },

    openViewer(images, index = 0) {
      store.setState(
        { ui: { viewer: { open: true, images: images || [], index: index || 0 } } },
        { type: "ui/viewer/open" }
      );
    },

    closeViewer() {
      store.setState({ ui: { viewer: { open: false, images: [], index: 0 } } }, { type: "ui/viewer/close" });
    },

    setViewerIndex(index) {
      const s = store.getState();
      const max = Math.max(0, (s.ui.viewer.images?.length || 0) - 1);
      const clamped = Math.min(Math.max(0, index), max);
      store.setState({ ui: { viewer: { ...s.ui.viewer, index: clamped } } }, { type: "ui/viewer/index" });
    },

    setAuthMode(mode) {
      store.setState({ auth: { mode } }, { type: "auth/mode" });
    },

    setSearchFilters(patch) {
      const s = store.getState();
      store.setState(
        { search: { filters: { ...s.search.filters, ...patch } } },
        { type: "search/filters" }
      );
    },
  },
};
