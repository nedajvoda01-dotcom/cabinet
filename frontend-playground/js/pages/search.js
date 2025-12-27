import { store } from "../store.js";
import { Tabs } from "../ui/tabs.js";
import { Segmented } from "../ui/segmented.js";
import { Input } from "../ui/input.js";
import { Button } from "../ui/button.js";

export function renderSearchPage(outlet) {
  outlet.innerHTML = "";

  const root = document.createElement("div");
  root.className = "search-page";

  // ===== header card (controls) =====
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

  // Tabs: active/archived
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

  // Row: segmented controls
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

  // Right buttons
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
      // пока просто алерт — позже будет подгрузка/рендер списка
      const n = calcResultsCount(store.getState().search.filters);
      alert(`Показать ${n} объявлений (пока заглушка)`);
    },
  });

  rightBtns.appendChild(resetBtn.el);
  rightBtns.appendChild(showBtn.el);

  segRow.appendChild(leftSeg);
  segRow.appendChild(rightBtns);

  // Filters grid (пока Input'ы вместо Select'ов — дизайн потом)
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
    onClick: () => alert("Панель всех параметров — следующий шаг"),
  });

  filtersGrid.appendChild(brand.el);
  filtersGrid.appendChild(model.el);
  filtersGrid.appendChild(generation.el);
  filtersGrid.appendChild(color.el);
  filtersGrid.appendChild(region.el);

  // чтобы сетка была ровно 3 колонки, а кнопка заняла 1 ячейку:
  const btnWrap = document.createElement("div");
  btnWrap.appendChild(allParamsBtn.el);
  filtersGrid.appendChild(btnWrap);

  controlsCard.appendChild(headerRow);
  controlsCard.appendChild(segRow);
  controlsCard.appendChild(filtersGrid);

  // ===== results card (placeholder) =====
  const resultsCard = document.createElement("div");
  resultsCard.className = "search-card";
  resultsCard.innerHTML = `
    <div style="font-weight:800;margin-bottom:8px">Результаты</div>
    <div style="color:var(--muted)">Пока заглушка. Следующий шаг — карточки объявлений и viewer фото.</div>
  `;

  root.appendChild(controlsCard);
  root.appendChild(resultsCard);
  outlet.appendChild(root);

  // ===== derived updates =====
  function updateDerived() {
    const n = calcResultsCount(store.getState().search.filters);
    showBtn.el.textContent = `Показать ${n}`;
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

  // подписки: обновляем только то, что нужно
  const unsub = store.subscribe((st, meta) => {
    if (meta?.type === "search/filters") {
      // синхронизируем UI контролы с состоянием (если сбросили)
      tabs.setValue(st.search.filters.status);
      condSeg.setValue(st.search.filters.condition);
      sellerSeg.setValue(st.search.filters.seller);

      // синхроним input'ы
      brand.input.value = st.search.filters.brand;
      model.input.value = st.search.filters.model;
      generation.input.value = st.search.filters.generation;
      color.input.value = st.search.filters.color;
      region.input.value = st.search.filters.region;

      updateDerived();
    }
  });

  updateDerived();

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
    },
  };
}

/**
 * Пока простой “счётчик результатов” без моков:
 * чем больше заполнено фильтров — тем меньше результатов.
 * Потом заменим на реальные mocks/listings.
 */
function calcResultsCount(filters) {
  const base = 248;
  let penalty = 0;

  if (filters.status === "archived") penalty += 70;
  if (filters.condition !== "all") penalty += 40;
  if (filters.seller !== "all") penalty += 30;

  const textFields = ["brand", "model", "generation", "color", "region"];
  for (const k of textFields) {
    const v = (filters[k] || "").trim();
    if (!v) continue;
    penalty += Math.min(35, 10 + v.length * 2);
  }

  return Math.max(0, base - penalty);
}
