export function renderSearchPage(outlet) {
  outlet.innerHTML = `
    <div style="background:#fff;border-radius:18px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,.06)">
      <div style="font-weight:800;font-size:20px;margin-bottom:8px">Поиск автомобиля</div>
      <div style="color:#777">Следующий шаг — делаем UI-kit (Button/Input/Tabs) и панель фильтров 1:1.</div>
    </div>
  `;

  return { unmount() {} };
}
