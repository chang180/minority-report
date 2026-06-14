# Worker Brief — Gate M8-B

**Milestone 8 · Grounding v1 + Trust cap + Admin 設定**  
**前置**：M7 **RELEASED**  
**狀態**：**可開工**（2026-06-14）

> User 優先：**Grounding** 為 M8 核心。Admin 可設定後端（本機 LLM `web_search` tool loop / Search API / 關閉）。Domain consensus Cases **MUST NOT** 改動。

---

## 角色

Worker Agent。**只做 M8-B**：Grounding fetch、Admin 設定、metadata 注入、Trust cap 調整、UI、測試。

---

## 必讀

1. **[docs/09-grounding-and-trust.md](../../../../docs/09-grounding-and-trust.md)**（全文）
2. [docs/04-trust-level.md §3.1](../../../../docs/04-trust-level.md)（Type C cap）
3. [docs/02-contracts.md §10](../../../../docs/02-contracts.md)（audit）
4. 現有：`AuthVerificationController`、`ConsensusWorkflow`、`CascadeTrustLevelScorer`、`AdminDemoController`
5. 本 brief · [progress.md](progress.md)

---

## 背景

| 已有 | 缺口 |
|------|------|
| `grounding_available` 欄位 + workflow 讀 metadata | 恆为 `false` |
| Admin `/admin/demo` | 無 Grounding 設定 |
| Type C Trust | 永遠被 `type_c_no_grounding` cap 在 Low |
| 本機 dev | `LOCAL_AI_API_URL` + Gemma `web_search` tool calling 已驗證 |

**D2 決策**：Grounding 後端 **Admin 可選** — `local_llm_tool_loop` | `search_api` | `disabled`（見 09 §3–§4）。

---

## 交付物

### 1. 資料庫與 Models

- [ ] Migration + Model：`system_grounding_settings`（singleton；見 09 §3）
- [ ] `encrypted` cast：`local_api_key`、`search_api_key`
- [ ] Seeder：dev 預設 `local_llm_tool_loop` + `LOCAL_AI_API_URL`

### 2. Grounding 模組 — `app/Grounding/`

**MUST NOT** 在 `app/Consensus/` 呼叫 HTTP / AI SDK。

- [ ] `GroundingProvider` contract + `GroundingService`
- [ ] `DisabledGroundingProvider`
- [ ] `LocalLlmWebSearchGroundingProvider` — OpenAI-compatible chat + **應用層 tool loop**（09 §4.2）
- [ ] `SearchApiGroundingProvider` — 至少實作 **一種**（建議 Tavily 或 Serper；DuckDuckGo lite 可作 fallback）
- [ ] `GroundingResult` DTO（status、sources、summary、grounding_available）
- [ ] 共用 `WebSearchExecutor`（供 local tool loop 與 search_api 重用）

**Tool loop 要點**（已實測 llama.cpp）：

```text
POST /v1/chat/completions + tools[web_search]
  ← tool_calls
execute search → role: tool
  ← repeat → final answer + sources
```

### 3. HTTP 整合

- [ ] `AdminGroundingController` — `GET/PUT /admin/grounding`
- [ ] `AuthVerificationController::store` — run Grounding **before** workflow；merge metadata
- [ ] `VerificationController::store`（demo）— **MAY** 同樣注入（Admin mode ≠ disabled 時）
- [ ] **MUST NOT** 改 `ConsensusWorkflow` 簽名；可傳 `$providerPrompt` 附加 grounding summary（09 §7）

### 4. Trust

- [ ] `CascadeTrustLevelScorer`：**最小 diff** — `grounding_available=true` 且 metadata grounding status=`success` 時 **不套用** `type_c_no_grounding` cap（09 §9）
- [ ] **MUST NOT** 破壞 F01–F14（無 grounding 路徑）

### 5. 前端（繁體中文）

- [ ] `Pages/admin/GroundingSettings.vue`
- [ ] `Verification/Show.vue` — grounding 區塊（status、sources、summary）
- [ ] `AppLayout` — admin「Grounding 設定」nav
- [ ] **MUST NOT** 引入 vue-i18n

### 6. Feature Tests

- [ ] `tests/Feature/M8BGroundingTest.php` — admin CRUD、mode 行為、metadata、no raw keys
- [ ] Trust unit/feature：F04 仍 Low 無 grounding；F15 或等價 mock success → High
- [ ] 全 suite 綠

---

## 驗收命令

```text
npm run typecheck
vendor/bin/pint --dirty --format agent
php artisan test --compact
php artisan test --filter=M8B
```

---

## MUST NOT

- 改寫 consensus Cases 1–6、`ConsensusAnalyzer` 判定
- 完整 RAG、Evidence LLM 裁定、semantic aligner（屬 M8-C）
- M8-A 範圍（verification 列表、async Job、email verification）
- 修改 `docs/`、根 `README.md`（建議寫 progress §4）
- API key 出現在 audit / log / Inertia

---

## 完成後交還

1. 更新 [progress.md](progress.md) §1–4
2. §4 列「建議 Orchestrator 文件更新」（若需 `.env.example` 新變數）
3. 使用者轉交 Orchestrator **審核 RELEASED**

---

## 建議實作順序

```text
1. Migration + Model + Seeder + GroundingResult DTO
2. SearchApiGroundingProvider + WebSearchExecutor
3. LocalLlmWebSearchGroundingProvider（tool loop）
4. GroundingService + Admin controller + Vue
5. Wire AuthVerificationController + demo optional
6. Trust cap + F15 tests
7. Show.vue grounding UI + full suite
```
