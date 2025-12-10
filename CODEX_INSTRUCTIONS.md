# CODEX_INSTRUCTIONS.md — How to Execute Autocontent Plan with Codex

Этот файл — **инструкция для Codex**, как читать `cabinet/PLANS.md` и выполнять работу слоями.

**Source of truth поведения:** `cabinet/AUTOCONTENT_SPEC.md`  
**Границы слоёв/правила:** `cabinet/AGENTS.md`  
**Детальный roadmap:** `cabinet/PLANS.md`

---

## 1) Общие правила

1. Работай **строго слоями** из `cabinet/PLANS.md`.
2. **Один слой = один PR.**
3. В одном PR **нельзя**:
   - делать следующий слой,
   - менять top-level структуру репозитория,
   - добавлять бизнес‑логику в Adapters,
   - ломать границы из `cabinet/AGENTS.md`.
4. Если обнаружен конфликт Spec ↔ код ↔ план:
   - опиши блокер в PR,
   - **остановись**.
5. В каждом PR обязательно:
   - изменения только текущего слоя,
   - тесты/фиксы тестов для текущего слоя,
   - короткий CHANGELOG в описании PR,
   - список файлов: created / renamed / modified.

---

## 2) Как начать работу (один раз)

Отправь Codex этот мастер‑промпт:

> Codex, открой `cabinet/PLANS.md`, `cabinet/AUTOCONTENT_SPEC.md`, `cabinet/AGENTS.md`.  
> Работай строго по слоям. Каждый слой — отдельный PR.  
> В PR следуй DoD слоя и Implementation Notes.  
> Если есть конфликт со Spec или границами — выпиши blockers в PR и остановись.

---

## 3) Как давать команду на следующий слой (каждый раз)

После мержа предыдущего PR отправляй Codex:

> Codex, реализуй **следующий невыполненный слой** из `cabinet/PLANS.md` целиком как **один PR**.  
> Строго следуй пунктам слоя и Implementation Notes.  
> Не делай изменений следующих слоёв в этом PR.  
> После PR остановись и выведи краткий отчет:
> - какие файлы создал/переименовал/обновил  
> - какие тесты добавил/исправил  
> - что осталось блокером (если есть).

---

## 4) Контроль качества (чек‑лист для PR)

Перед тем как завершить PR, убедись:

- [ ] Слой выполнен полностью по DoD из `PLANS.md`  
- [ ] Не затронуты другие слои  
- [ ] Нет новых top‑level папок  
- [ ] Modules используют только порты (interfaces), не реализации  
- [ ] Adapters не содержат бизнес‑логики  
- [ ] Workers не импортируют Controllers/Routes  
- [ ] Весь добавленный код покрыт тестами слоя  
- [ ] Ошибки/ретраи/WS соответствуют Spec  
- [ ] В PR есть CHANGELOG и список файлов

---

## 5) Порядок слоёв (кратко)

0. Spec as SoT  
1. Ports + neutral naming  
2. Fail‑fast contracts  
3. Consumer contract tests  
4. Fake adapters  
5. Unified retry/idempotency  
6. Drivers/feature flags  
7. Avito as domain target  
8. Spec‑max modules (Parser → Photos → Export → Publish → Robot → Admin → Core)  
9. E2E + boundary audit

Полное описание каждого слоя — в `cabinet/PLANS.md`.
