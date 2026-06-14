# M6-A Progress — Minimal UI

| 欄位 | 值 |
|------|-----|
| Gate | **M6-A** |
| 狀態 | **RELEASED** |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 實作摘要

- Inertia workflow：`GET /`（Index）→ `POST /verifications` → `GET /verifications/{id}`（Show）
- `ConsensusDemoFixtureCatalog`：5 個 fake demo fixtures
- 結果頁：consensus、trust、verdict/minority report、provider 比對

## 2. 交付物對照

- [x] 問題輸入頁、結果頁、Provider 比對、fake demo、Feature test

## 3. 驗收

```text
M6MinimalUiTest: 4 passed (78 assertions)
npm run typecheck: passed
Full suite: 1 skipped, 94 passed (562 assertions)
```

## 4. Worker 提交 / 5. Orchestrator 審核

| Worker 日期 | 2026-06-14 |
| **建議 Orchestrator 文件更新** | gate-status M6 RELEASED；README UI 入口 `/`。 |
| 審核者 | Orchestrator |
| 日期 | 2026-06-14 |
| 結果 | ☑ **RELEASED** · ☐ REJECTED |
| **docs / README 整合** | ☑ 已更新 |
| M6 Milestone | ☑ **RELEASED** · **M1–M6 MVP 完成** |
| Blocking | 無 |
| Non-blocking | UI 目前僅 fake fixture demo；真 provider 路徑留待後續產品迭代。 |
| 備註 | 原 `Welcome` 首頁改為 Verification Index；`/health` 保留。 |
