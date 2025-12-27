import { store } from "../store.js";

import { Tabs } from "../ui/tabs.js";
import { Segmented } from "../ui/segmented.js";
import { Input } from "../ui/input.js";
import { Button } from "../ui/button.js";

import { ListingCard } from "../components/listing-card.js";

export function renderSearchPage(outlet) {
  outlet.innerHTML = "";

  const root = document.createElement("div");
  root.className = "search-page";

  // ====== Controls card ======
  const controlsCard = document.createElement("div");
  controlsCard.className = "search-card";

  const headerRow = document.createElement("div");
  headerRow.style.display = "flex";
  headerRow.style.alignItems = "center";
  headerRow.style.justifyContent = "space-between";
  headerRow.style.gap = "16px";

  const title = document.createElement("div");
  title.className = "search-title";
  title.textContent = "Поиск автомобиля";

  const s0 = store.getState();

  const tabs = Tabs({
    items: [
      { key: "active", label: "Активные" },
      { key: "archived", label: "Архив" },
    ],
    value: s0.search.filters.status,
    onChange: (key) => store.actions.setSearchFilters({ status: key }),
  });

  headerRow.appendChild(title);
  headerRow.appendChild(tabs.el);

  const segRow = document.createElement("div");
  segRow.style.display = "flex";
  segRow.style.alignItems = "center";
  segRow.style.justifyContent = "space-between";
  segRow.style.gap = "16px";
  segRow.style.marginTop = "12px";

  const leftSeg = document.createElement("div");
  leftSeg.style.display = "flex";
  leftSeg.style.gap = "12px";
  leftSeg.style.alignItems = "center";
  leftSeg.style.flexWrap = "wrap";

  const condSeg = Segmented({
    items: [
      { key: "all", label: "Все" },
      { key: "used", label: "С пробегом" },
      { key: "new", label: "Новые" },
    ],
    value: s0.search.filters.condition,
    onChange: (key) => store.actions.setSearchFilters({ condition: key }),
  });

  const sellerSeg = Segmented({
    items: [
      { key: "all", label: "Все" },
      { key: "owner", label: "Собственник" },
      { key: "dealer", label: "Дилер" },
    ],
    value: s0.search.filters.seller,
    onChange: (key) => store.actions.setSearchFilters({ seller: key }),
  });

  leftSeg.appendChild(condSeg.el);
  leftSeg.appendChild(sellerSeg.el);

  const rightBtns = document.createElement("div");
  rightBtns.style.display = "flex";
  rightBtns.style.gap = "10px";
  rightBtns.style.alignItems = "center";

  const resetBtn = Button({
    text: "Сбросить",
    variant: "ghost",
    size: "sm",
    onClick: () => resetFilters(),
  });

  const showBtn = Button({
    text: "Показать 0",
    variant: "primary",
    size: "sm",
    onClick: () => {
      resultsCard.scrollIntoView({ behavior: "smooth", block: "start" });
    },
  });

  rightBtns.appendChild(resetBtn.el);
  rightBtns.appendChild(showBtn.el);

  segRow.appendChild(leftSeg);
  segRow.appendChild(rightBtns);

  const filtersGrid = document.createElement("div");
  filtersGrid.style.display = "grid";
  filtersGrid.style.gridTemplateColumns = "repeat(3, minmax(0, 1fr))";
  filtersGrid.style.gap = "12px";
  filtersGrid.style.marginTop = "14px";

  const brand = Input({
    placeholder: "Марка",
    value: s0.search.filters.brand,
    onInput: (v) => store.actions.setSearchFilters({ brand: v }),
  });

  const model = Input({
    placeholder: "Модель",
    value: s0.search.filters.model,
    onInput: (v) => store.actions.setSearchFilters({ model: v }),
  });

  const generation = Input({
    placeholder: "Поколение",
    value: s0.search.filters.generation,
    onInput: (v) => store.actions.setSearchFilters({ generation: v }),
  });

  const color = Input({
    placeholder: "Цвет",
    value: s0.search.filters.color,
    onInput: (v) => store.actions.setSearchFilters({ color: v }),
  });

  const region = Input({
    placeholder: "Регион",
    value: s0.search.filters.region,
    onInput: (v) => store.actions.setSearchFilters({ region: v }),
  });

  const allParamsBtn = Button({
    text: "Все параметры",
    variant: "ghost",
    size: "md",
    fullWidth: true,
    onClick: () => alert("Панель всех параметров — следующий шаг (после дизайна)"),
  });

  filtersGrid.appendChild(brand.el);
  filtersGrid.appendChild(model.el);
  filtersGrid.appendChild(generation.el);
  filtersGrid.appendChild(color.el);
  filtersGrid.appendChild(region.el);

  const btnWrap = document.createElement("div");
  btnWrap.appendChild(allParamsBtn.el);
  filtersGrid.appendChild(btnWrap);

  controlsCard.appendChild(headerRow);
  controlsCard.appendChild(segRow);
  controlsCard.appendChild(filtersGrid);

  // ====== Results card ======
  const resultsCard = document.createElement("div");
  resultsCard.className = "search-card";

  const resultsHead = document.createElement("div");
  resultsHead.className = "results-head";

  const resultsTitle = document.createElement("div");
  resultsTitle.style.fontWeight = "900";
  resultsTitle.textContent = "Результаты";

  const resultsCount = document.createElement("div");
  resultsCount.className = "results-count";

  resultsHead.appendChild(resultsTitle);
  resultsHead.appendChild(resultsCount);

  const listEl = document.createElement("div");
  listEl.className = "results-list";

  resultsCard.appendChild(resultsHead);
  resultsCard.appendChild(listEl);

  root.appendChild(controlsCard);
  root.appendChild(resultsCard);
  outlet.appendChild(root);

  // ===== rendering cards =====
  let mountedCards = [];

  function renderResults() {
    mountedCards.forEach((c) => {
      try { c.unmount?.(); } catch {}
    });
    mountedCards = [];
    listEl.innerHTML = "";

    const st = store.getState();
    const items = st.search.results || [];

    resultsCount.textContent = `${items.length} найдено`;
    showBtn.el.textContent = `Показать ${items.length}`;

    if (items.length === 0) {
      const empty = document.createElement("div");
      empty.style.color = "var(--muted)";
      empty.style.fontWeight = "600";
      empty.textContent = "Ничего не найдено. Попробуйте изменить фильтры.";
      listEl.appendChild(empty);
      return;
    }

    for (const it of items) {
      const card = ListingCard({
        listing: it,
        onOpenPhotos: (listing) => {
          store.actions.openViewer(listing.photos || [], 0);
        },
      });
      mountedCards.push(card);
      listEl.appendChild(card.el);
    }
  }

  function resetFilters() {
    store.actions.setSearchFilters({
      status: "active",
      condition: "all",
      seller: "all",
      brand: "",
      model: "",
      generation: "",
      color: "",
      region: "",
    });
  }

  const unsub = store.subscribe((st, meta) => {
    if (meta?.type === "search/filters" || meta?.type === "search/init") {
      tabs.setValue(st.search.filters.status);
      condSeg.setValue(st.search.filters.condition);
      sellerSeg.setValue(st.search.filters.seller);

      brand.input.value = st.search.filters.brand;
      model.input.value = st.search.filters.model;
      generation.input.value = st.search.filters.generation;
      color.input.value = st.search.filters.color;
      region.input.value = st.search.filters.region;

      renderResults();
    }
  });

  renderResults();

  return {
    unmount() {
      try { unsub(); } catch {}

      try { tabs.unmount(); } catch {}
      try { condSeg.unmount(); } catch {}
      try { sellerSeg.unmount(); } catch {}

      try { brand.unmount(); } catch {}
      try { model.unmount(); } catch {}
      try { generation.unmount(); } catch {}
      try { color.unmount(); } catch {}
      try { region.unmount(); } catch {}

      try { resetBtn.unmount(); } catch {}
      try { showBtn.unmount(); } catch {}
      try { allParamsBtn.unmount(); } catch {}

      mountedCards.forEach((c) => {
        try { c.unmount?.(); } catch {}
      });
      mountedCards = [];
    },
  };
}
