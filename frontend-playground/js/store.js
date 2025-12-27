import { listings as mockListings } from "./mocks/listings.js";

const STORAGE_KEY = "fp_state_v1";

const defaultState = {
  ui: {
    sidebarCollapsed: false,
    viewer: {
      open: false,
      images: [],
      index: 0,
    },
  },

  auth: {
    mode: "login",
  },

  search: {
    filters: {
      status: "active",      // active | archived
      condition: "all",      // all | used | new
      seller: "all",         // all | owner | dealer
      brand: "",
      model: "",
      generation: "",
      color: "",
      region: "",
    },
    all: [],        // все объявления (моки)
    results: [],    // отфильтрованные
  },
};

/* =========================================================
   Helpers
========================================================= */

function loadPersisted() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? JSON.parse(raw) : {};
  } catch {
    return {};
  }
}

function savePersisted(state) {
  const persisted = {
    ui: {
      sidebarCollapsed: state.ui.sidebarCollapsed,
    },
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

/* =========================================================
   Filtering logic (ядро поиска)
========================================================= */

function normalize(v) {
  return String(v || "").toLowerCase();
}

function applyFilters(list, f) {
  return list.filter((it) => {
    if (f.status !== it.status) return false;

    if (f.condition !== "all" && it.condition !== f.condition) return false;
    if (f.seller !== "all" && it.seller !== f.seller) return false;

    if (f.brand && !normalize(it.brand).includes(normalize(f.brand))) return false;
    if (f.model && !normalize(it.model).includes(normalize(f.model))) return false;
    if (f.generation && !normalize(it.generation).includes(normalize(f.generation))) return false;
    if (f.color && !normalize(it.color).includes(normalize(f.color))) return false;
    if (f.region && !normalize(it.region).includes(normalize(f.region))) return false;

    return true;
  });
}

/* =========================================================
   Store core
========================================================= */

let state = deepMerge(defaultState, loadPersisted());
const listeners = new Set();

function recomputeSearch(nextState) {
  nextState.search.results = applyFilters(
    nextState.search.all,
    nextState.search.filters
  );
}

export const store = {
  getState() {
    return state;
  },

  setState(patch, meta = {}) {
    const next = deepMerge(state, patch);

    // если меняются фильтры или список — пересчитываем результаты
    if (
      meta.type === "search/filters" ||
      meta.type === "search/init"
    ) {
      recomputeSearch(next);
    }

    state = next;
    savePersisted(state);
    listeners.forEach((fn) => fn(state, meta));
  },

  subscribe(fn) {
    listeners.add(fn);
    return () => listeners.delete(fn);
  },

  actions: {
    /* ===== UI ===== */

    setSidebarCollapsed(value) {
      store.setState(
        { ui: { sidebarCollapsed: !!value } },
        { type: "ui/sidebar" }
      );
    },

    openViewer(images, index = 0) {
      store.setState(
        { ui: { viewer: { open: true, images, index } } },
        { type: "ui/viewer/open" }
      );
    },

    closeViewer() {
      store.setState(
        { ui: { viewer: { open: false, images: [], index: 0 } } },
        { type: "ui/viewer/close" }
      );
    },

    /* ===== Auth ===== */

    setAuthMode(mode) {
      store.setState({ auth: { mode } }, { type: "auth/mode" });
    },

    /* ===== Search ===== */

    initSearch() {
      store.setState(
        {
          search: {
            ...state.search,
            all: mockListings.slice(),
          },
        },
        { type: "search/init" }
      );
    },

    setSearchFilters(patch) {
      store.setState(
        {
          search: {
            ...state.search,
            filters: {
              ...state.search.filters,
              ...patch,
            },
          },
        },
        { type: "search/filters" }
      );
    },
  },
};

/* =========================================================
   Init
========================================================= */

// инициализируем поиск сразу
store.actions.initSearch();
