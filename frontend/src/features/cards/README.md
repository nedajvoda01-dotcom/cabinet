# Cards feature (cabinet/frontend/src/features/cards)

Рабочий домен Operator UI. Cards — “источник истины” и центр pipeline.  
Operator UI должен быстро доводить карточку до публикации.  

## Responsibilities
- Список карточек с фильтрами по статусам/поиском.
- Страница карточки:
  - отображение нормализованных полей (vehicle/price/location/description),
  - просмотр и ручная корректировка фото/порядка (в части Cards UI),
  - показ прогресса стадий pipeline,
  - показывать ошибки последней стадии.
- Кнопки запуска стадий строго по StateMachine:
  - draft → Start Photos
  - photos_failed → Retry Photos
  - photos_ready → Start Export
  - export_failed → Retry Export
  - ready_for_publish → Start Publish
  - publish_failed → Retry Publish
  UI обязан скрывать недопустимые действия. 【Spec Part 10 / Appendix E】

## Data model
Минимальный Card (MVP):
- id, source, source_id
- status (state machine)
- vehicle {make, model, year, body, mileage, vin?}
- price {value, currency}
- location {city, address?, coords?}
- description
- photos [{id, raw_url, masked_url, order, status}]
- export_refs[], publish_refs[]
- created_at, updated_at 【Spec Part 5/9】

## API
- `GET /cards`
- `GET /cards/:id`
- `POST /cards`
- `PATCH /cards/:id`
- `DELETE /cards/:id`
- pipeline triggers:
  - `POST /cards/:id/photos`   (start/retry photos)
  - `POST /cards/:id/export`   (start/retry export)
  - `POST /cards/:id/publish`  (start/retry publish)

## WS events
Cards UI может обновляться лайвом:
- `card.status.updated`
- `photos.progress`
- `export.progress`
- `publish.progress`
- `publish.status.updated`

## Exports
`index.ts` — barrel: types/schemas/api/ui.
