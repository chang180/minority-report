# M8-B Progress — Grounding v1

| 欄位 | 值 |
|------|-----|
| Gate | **M8-B** |
| 狀態 | **RELEASED** |
| Brief | [brief.md](brief.md) |
| Spec | [docs/09-grounding-and-trust.md](../../../../docs/09-grounding-and-trust.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 實作摘要

### 資料庫與 Models
- `system_grounding_settings` 表 + `SystemGroundingSettings` Model（singleton，含 encrypted cast for `local_api_key`/`search_api_key`）
- `SystemGroundingSettingsSeeder` — dev 預設 `local_llm_tool_loop` + `LOCAL_AI_API_URL`

### Grounding 模組 `app/Grounding/`
- `Contracts/GroundingProvider` — interface
- `DTO/GroundingResult` + `DTO/GroundingSource` — 契約 DTO
- `Providers/DisabledGroundingProvider` — skipped result
- `Providers/LocalLlmWebSearchGroundingProvider` — OpenAI-compatible tool loop（應用層 web_search）
- `Providers/SearchApiGroundingProvider` — 直接 Search API
- `WebSearchExecutor` — 共用 HTTP 搜尋（Tavily / Serper / DuckDuckGo lite）
- `GroundingService` — 依 Admin 設定解析 provider，skip when `requiresGrounding=false`

### HTTP 整合
- `AdminGroundingController` — `GET/PUT /admin/grounding`（keys 僅以 `has_*` 曝露）
- `AuthVerificationController::store` — fetch grounding before workflow；merge metadata；grounding summary 附加至 `$providerPrompt`
- `VerificationController::store`（demo）— 同樣注入 grounding

### Trust
- `CascadeTrustLevelScorer` — 最小 diff：`grounding_available=true` 且 `metadata.grounding_status=success` 時不套用 `type_c_no_grounding` cap
- `ConsensusWorkflow` — 內部 propagate `groundingStatus` 至 `AnalysisContext.metadata`

### 前端（繁體中文）
- `Pages/admin/GroundingSettings.vue` — mode 選擇、local/search 設定、enabled 開關
- `Verification/Show.vue` — grounding 區塊（status、sources、summary；MUST NOT 顯示 key）
- `AppLayout.vue` — admin nav 新增「Grounding 設定」（Globe icon）

### 測試
- `tests/Feature/M8BGroundingTest.php` — 12 tests：Admin CRUD、mode 行為、metadata、no raw keys
- `tests/Unit/Consensus/Scorer/TrustLevelDecisionTableTest.php` — F15、F15-partial、F4 regression

---

## 2. 交付物對照

- [x] `system_grounding_settings` + Admin UI + seeder
- [x] `GroundingService` + 三 mode strategy
- [x] `local_llm_tool_loop` tool loop（web_search）
- [x] Auth + demo verification 注入 grounding metadata
- [x] Trust cap 調整 + F04/F15 測試
- [x] `Verification/Show` grounding 區塊（繁中）
- [x] `npm run typecheck` 通過；全 suite 綠（146 passed, 1 skipped）
- [x] **MUST NOT** 改 `app/Consensus/` 算法（scorer 最小 cap 條件 diff 除外）

---

## 3. 驗收

```text
npm run typecheck       ✓ (no errors)
vendor/bin/pint --dirty ✓ (auto-fixed)
php artisan test --compact            ✓ 146 passed, 1 skipped
php artisan test --filter=M8B        ✓ 12 passed
```

---

## 4. Worker 提交

| Worker 日期 | 2026-06-14 |
| **建議 Orchestrator 文件更新** | 新增 `.env.example` 變數：`LOCAL_AI_API_URL`（已存在於 seeder 讀取）、`OPENAI_MODEL`（用於 local_model 預設） |

---

## 5. Orchestrator 審核

| 審核者 | Orchestrator |
| 結果 | ☑ **RELEASED** |
| 驗收 | typecheck ✓ · pint ✓ · 146 tests passed · M8B 12 passed |
| 備註 | Grounding 三 mode + Trust F15/F04；**M8-A 刻意後做**（見 roadmap §3） |
