import { Button } from "../ui/button.js";

export function ListingCard({ listing, onOpenPhotos } = {}) {
  const root = document.createElement("div");
  root.className = "listing-card";

  const thumb = (listing.photos && listing.photos[0]) || "";

  // Левая часть — превью
  const media = document.createElement("div");
  media.className = "listing-card__media";
  media.innerHTML = `
    <div class="listing-card__img" title="Открыть фото">
      ${thumb ? `<img src="${thumb}" alt="">` : `<div class="listing-card__img--empty">Нет фото</div>`}
    </div>
  `;

  // Контент
  const content = document.createElement("div");
  content.className = "listing-card__content";

  const title = document.createElement("div");
  title.className = "listing-card__title";
  title.textContent = `${listing.brand} ${listing.model}`;

  const subtitle = document.createElement("div");
  subtitle.className = "listing-card__subtitle";
  subtitle.textContent = `${listing.generation} • ${listing.year} • ${formatKm(listing.mileageKm)} • ${listing.color} • ${listing.region}`;

  const meta = document.createElement("div");
  meta.className = "listing-card__meta";
  meta.innerHTML = `
    <span class="tag">${listing.condition === "new" ? "Новый" : "С пробегом"}</span>
    <span class="tag">${listing.seller === "dealer" ? "Дилер" : "Собственник"}</span>
    <span class="tag">${listing.status === "archived" ? "Архив" : "Активно"}</span>
  `;

  const price = document.createElement("div");
  price.className = "listing-card__price";
  price.textContent = formatRub(listing.priceRub);

  const actions = document.createElement("div");
  actions.className = "listing-card__actions";

  const openBtn = Button({
    text: "Открыть",
    variant: "ghost",
    size: "sm",
    onClick: () => {
      // пока заглушка. Потом сделаем route #listing?id=...
      alert(`Открыть объявление ${listing.id} (заглушка)`);
    },
  });

  actions.appendChild(openBtn.el);

  content.appendChild(title);
  content.appendChild(subtitle);
  content.appendChild(meta);
  content.appendChild(price);
  content.appendChild(actions);

  root.appendChild(media);
  root.appendChild(content);

  // клики по картинке
  const onMediaClick = () => {
    if (typeof onOpenPhotos === "function") onOpenPhotos(listing);
  };
  media.querySelector(".listing-card__img").addEventListener("click", onMediaClick);

  return {
    el: root,
    unmount() {
      try { openBtn.unmount(); } catch {}
      try { media.querySelector(".listing-card__img")?.removeEventListener("click", onMediaClick); } catch {}
    },
  };
}

function formatRub(n) {
  const v = Number(n || 0);
  return v.toLocaleString("ru-RU") + " ₽";
}

function formatKm(n) {
  const v = Number(n || 0);
  if (v <= 0) return "0 км";
  return v.toLocaleString("ru-RU") + " км";
}
