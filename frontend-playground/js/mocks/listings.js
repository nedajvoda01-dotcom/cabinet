// mocks/listings.js
// Минимальный набор полей, который нам нужен для:
// - фильтрации
// - вывода карточки
// - viewer фото

export const listings = [
  {
    id: "a1",
    status: "active", // active | archived
    condition: "used", // used | new
    seller: "owner", // owner | dealer
    brand: "BMW",
    model: "X5",
    generation: "G05",
    color: "Черный",
    region: "Москва",
    year: 2021,
    mileageKm: 54000,
    priceRub: 6890000,
    photos: [
      // пока ссылки-заглушки; позже подложим реальные файлы в assets/images/
      "https://picsum.photos/seed/bmw-x5-1/1200/800",
      "https://picsum.photos/seed/bmw-x5-2/1200/800",
      "https://picsum.photos/seed/bmw-x5-3/1200/800",
      "https://picsum.photos/seed/bmw-x5-4/1200/800"
    ],
  },
  {
    id: "a2",
    status: "active",
    condition: "new",
    seller: "dealer",
    brand: "Toyota",
    model: "Camry",
    generation: "XV70",
    color: "Белый",
    region: "Санкт-Петербург",
    year: 2024,
    mileageKm: 0,
    priceRub: 4290000,
    photos: [
      "https://picsum.photos/seed/camry-1/1200/800",
      "https://picsum.photos/seed/camry-2/1200/800",
      "https://picsum.photos/seed/camry-3/1200/800"
    ],
  },
  {
    id: "a3",
    status: "archived",
    condition: "used",
    seller: "dealer",
    brand: "Mercedes-Benz",
    model: "E-Class",
    generation: "W213",
    color: "Серый",
    region: "Казань",
    year: 2020,
    mileageKm: 78000,
    priceRub: 4550000,
    photos: [
      "https://picsum.photos/seed/eclass-1/1200/800",
      "https://picsum.photos/seed/eclass-2/1200/800"
    ],
  },
  {
    id: "a4",
    status: "active",
    condition: "used",
    seller: "owner",
    brand: "LADA",
    model: "Vesta",
    generation: "I",
    color: "Синий",
    region: "Екатеринбург",
    year: 2019,
    mileageKm: 91000,
    priceRub: 890000,
    photos: [
      "https://picsum.photos/seed/vesta-1/1200/800",
      "https://picsum.photos/seed/vesta-2/1200/800",
      "https://picsum.photos/seed/vesta-3/1200/800"
    ],
  },
];
