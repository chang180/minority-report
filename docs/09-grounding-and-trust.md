# 09 — Grounding 與 Trust 擴充（Post-MVP · Milestone 8-B）

本文件定義 Milestone 8-B 的 **Grounding v1**、Admin 可設定後端、與 Trust cap 調整。  
M1–M7 consensus domain 算法 **MUST NOT** 因 M8-B 而改寫 Cases 1–6；M8-B 擴充 **應用層**（Grounding fetch、metadata 注入、Trust scorer 讀取真實 `grounding_available`）。

**前置**：M7 **RELEASED**。M8-B Worker **MUST** 先讀本文件、[07-milestones.md §M8-B](07-milestones.md)、Gate brief。

---

## 1. 範圍與邊界

### 1.1 M8-B 要做

| 領域 | 說明 |
|------|------|
| Grounding fetch | 對 `requires_grounding` 問題，於 consensus 前取得外部來源摘要 |
| Admin 設定 | 系統管理員選擇 grounding 後端（本機 LLM tool loop / Search API / 關閉） |
| Metadata 注入 | `Question.metadata` + `verification_requests` 持久化 grounding 狀態 |
| Trust 調整 | `grounding_available=true` 且 fetch 成功時，放寬 Type C 的 no-grounding cap |
| UI | Admin Grounding 設定頁；Verification Show 顯示 grounding 摘要（繁中） |
| 測試 | `M8BGroundingTest`、F15 fixture、F04 回歸 |

### 1.2 M8-B MUST NOT

- 完整 RAG / 向量資料庫
- Evidence Comparison「哪方證據較強」的 LLM 裁定
- 在 `app/Consensus/` 直接呼叫 HTTP、Search SDK 或 Laravel AI facade
- 改寫 [03-consensus-algorithm.md](03-consensus-algorithm.md) Cases 1–6
- 破壞 F01–F14 語意（F04 在 **無** grounding 時行為不變）
- 將 API key、search token 寫入 audit / log / Inertia props

### 1.3 與 M7 的關係

- **Consensus 三 provider** 仍用 M7-B `ConfiguredLlmProviderFactory::forUser()`（BYOK）
- **Grounding** 為 **獨立前置步驟**，使用 **Admin 設定的系統級** 後端（非 per-user BYOK）
- Demo 訪客 verification **MAY** 沿用同一 Admin grounding 設定（與 demo provider 設定無關）

---

## 2. 概念

### 2.1 欄位語意（延續 02 §2）

| 欄位 | 來源 | 說明 |
|------|------|------|
| `requires_grounding` | Classifier | 問題是否需要外部查證 |
| `grounding_available` | **Grounding runtime** | 本次 verification **是否**成功取得可用外部來源 |

**M8-B 前**：`grounding_available` 恆为 `false`。  
**M8-B 後**：由 Grounding 步驟設定；Classifier **MUST NOT** 自行設為 `true`。

### 2.2 Grounding 流程（高層）

```text
Question text
    → Classifier（既有）
    → [若 requires_grounding 且 Admin mode ≠ disabled]
         GroundingService::fetch()
    → 合併 metadata（grounding_*）
    → ConsensusWorkflow::run()（既有；讀 metadata.grounding_available）
    → Trust（CascadeTrustLevelScorer 讀 context.groundingAvailable）
```

Grounding **MUST** 在 `ConsensusWorkflow::run()` **之前**完成；**MUST NOT** 修改 `ConsensusWorkflow` 簽名。

---

## 3. Admin 設定 — `system_grounding_settings`

Singleton（一行或 key-value，與 `system_demo_settings` 同模式）。

| 欄位 | 型別 | 說明 |
|------|------|------|
| `mode` | string | `disabled` \| `local_llm_tool_loop` \| `search_api` |
| `local_api_url` | string | nullable；`local_llm_tool_loop` 時 OpenAI-compatible base URL |
| `local_model` | string | nullable；預設取自 `OPENAI_MODEL` / config |
| `local_api_key` | string | nullable；encrypted；預設 `local` |
| `search_provider` | string | nullable；`tavily` \| `serper` \| `duckduckgo_lite`（Worker 至少實作 **一種** + 可擴） |
| `search_api_key` | string | nullable；encrypted |
| `search_api_url` | string | nullable；自訂 endpoint override |
| `enabled` | boolean | 總開關；`false` 等同 `mode=disabled` |
| `max_tool_rounds` | int | `local_llm_tool_loop` 最大 tool loop 次數（預設 4） |
| `timeout_seconds` | int | Grounding 整體 timeout（預設 120） |

**Seeder 預設（local dev）**：

- `enabled=true`
- `mode=local_llm_tool_loop`
- `local_api_url` ← `LOCAL_AI_API_URL` 或 `http://localhost:8080`
- `local_model` ← `OPENAI_MODEL`

僅 `admin` **MAY** 更新；路由 `GET/PUT /admin/grounding`（見 §8）。

---

## 4. Grounding 後端 Strategy

`App\Grounding\Contracts\GroundingProvider`（或等價）**MUST** 存在；`GroundingService` 依 Admin 設定解析。

### 4.1 `disabled`

- 不呼叫任何外部服務
- 回傳 `grounding_available=false`，`status=skipped`

### 4.2 `local_llm_tool_loop`（dev 預設 · User 拍板 D2）

對 Admin 設定的 OpenAI-compatible API（如 llama.cpp `@ localhost:8080`）：

1. 送 chat completion，註冊 `web_search` function tool
2. 若 `finish_reason=tool_calls`：**應用層**執行 search（見 4.2.1），以 `role: tool` 回傳
3. 重複直到 `stop` 或達 `max_tool_rounds`
4. 解析最終回答 + 累積 sources → `GroundingResult`

**實測（2026-06-14）**：llama.cpp **不會**在單次 `/v1/chat/completions` 自動跑完 search；**MUST** 由 Laravel 實作 tool loop。

#### 4.2.1 Tool `web_search` 執行

當 mode 為 `local_llm_tool_loop` 且 model 呼叫 `web_search`：

- **SHOULD** 委派給 `search_api` 同款 executor（共用 HTTP search 實作），或
- **MAY** 使用 Admin 同列 `search_provider` 設定

**MUST NOT** 假設 llama-server 內建 `/tools` endpoint（可能 404）。

### 4.3 `search_api`

直接呼叫 Search API（Tavily / Serper / DuckDuckGo lite 等），不經 LLM tool loop。

- 輸入：問題文字（**MAY** 由簡單 template 轉為 query）
- 輸出：sources 列表 + 摘要文字

---

## 5. GroundingResult 契約

```json
{
  "status": "success",
  "grounding_available": true,
  "query": "current weather Taipei Taiwan",
  "summary": "According to ...",
  "sources": [
    {
      "title": "Example",
      "url": "https://example.com",
      "snippet": "..."
    }
  ],
  "provider_mode": "local_llm_tool_loop",
  "metadata": {
    "tool_rounds": 2,
    "duration_ms": 15000
  }
}
```

| `status` | `grounding_available` | 說明 |
|----------|----------------------|------|
| `success` | `true` | 至少 1 個 source 且 summary 非空 |
| `partial` | `false` | 有呼叫但無可用 source |
| `failed` | `false` | timeout / HTTP 錯誤 |
| `skipped` | `false` | mode disabled 或 `requires_grounding=false` |

**MUST NOT** 在 `sources` / audit 保存 API key。

---

## 6. Question metadata 注入

HTTP 層（`AuthVerificationController` / demo store）**MUST** 在 `ConsensusWorkflow::run()` 前：

```php
$grounding = $groundingService->fetch($questionText, $classification);
$metadata = array_merge($existingMetadata, [
    'grounding_available' => $grounding->groundingAvailable,
    'grounding' => $grounding->toMetadataArray(), // 無 secrets
]);
```

`ConsensusWorkflow` 已讀 `$question->metadata['grounding_available']` 寫入 DB — **MUST NOT** 改 domain。

### 6.1 Audit 擴充（相對 02 §10）

| 欄位 | 說明 |
|------|------|
| `metadata.grounding.status` | `success` \| `partial` \| `failed` \| `skipped` |
| `metadata.grounding.provider_mode` | Admin mode |
| `metadata.grounding.sources` | `{title, url, snippet}` 陣列（無 key） |
| `metadata.grounding.summary` | 供 UI / prompt 附加之摘要 |

---

## 7. Consensus prompt 附加（應用層）

當 `grounding_available=true`，HTTP 層 **MAY** 將 grounding summary 附加至 provider prompt（**不**改 `ConsensusWorkflow::buildProviderPrompt` 簽名時，於 controller 傳入 `$providerPrompt` 參數）。

格式（英文即可，domain 契約）：

```text
External grounding summary (non-authoritative, for reference):
{summary}

Sources:
- {title}: {url}
...
```

**MUST NOT** 宣稱 grounding 摘要等於事實裁定（Consensus ≠ Correctness）。

---

## 8. HTTP 與 UI

| 路由 | 方法 | 存取 | 說明 |
|------|------|------|------|
| `/admin/grounding` | GET, PUT | auth + admin | Grounding 設定 UI + 儲存 |

Inertia 頁面 `Pages/admin/GroundingSettings.vue`（繁中）：

- mode 選擇
- local API URL / model
- search provider + key（僅顯示 has_key）
- enabled 開關

`Verification/Show.vue` **SHOULD** 顯示：

- 是否需要 grounding / 是否取得
- sources 列表（title + 連結）
- **MUST NOT** 顯示 API key

`AppLayout` admin nav **SHOULD** 新增「Grounding 設定」。

---

## 9. Trust Level 調整（M8-B）

延續 [04-trust-level.md](04-trust-level.md) base + caps 瀑布；**僅**調整 Type C 與 grounding 相關 cap。

### 9.1 Cap 規則更新

| 條件 | cap | 備註 |
|------|-----|------|
| Type C 且 `grounding_available = false` | Low | **不變**（F04） |
| Type C 且 `grounding_available = true` 且 `metadata.grounding.status = success` | **不套用** `type_c_no_grounding` | 允許依 base 與其他 caps 達更高 trust |
| Type C 且 grounding `partial` / `failed` | Low | 同無 grounding |

**M8-B v1 MUST NOT**：

- 僅因 grounding 成功即保證 High
- 引入「官方來源驗證」cap 解除（屬 Phase 3+）

### 9.2 預期 Trust 變化（示例）

| 情境 | M7（無 grounding） | M8-B（grounding success） |
|------|-------------------|---------------------------|
| F04：Type C Full 3/3 | Low（C cap） | **High**（無 C no-grounding cap） |
| Type C Majority | Low | **Medium**（base Medium，無 C cap） |
| Type C + major conflict | Low | Low（衝突 cap 仍生效） |

`CascadeTrustLevelScorer` **MAY** 最小 diff：僅當 `context.groundingAvailable === true` 且 metadata 標 success 時，**不加入** `type_c_no_grounding` cap。

---

## 10. 測試

### 10.1 Feature tests

- `M8BGroundingTest.php`：
  - Admin 可更新 settings；非 admin 403
  - `disabled` → `grounding_available=false`
  - mock `GroundingProvider` → auth verification metadata 含 grounding
  - encrypted search key 不回傳 raw value
- Trust regression：F04 mock grounding success → final_trust **≠ Low**（when Full 3/3）

### 10.2 Fixture F15（新增）

| 項目 | 值 |
|------|-----|
| 名稱 | F15 — Type C With Grounding Success |
| 輸入 | Type C、`requires_grounding=true`、`grounding_available=true`、三 provider Full |
| 期望 | `final_trust = High`（無 `type_c_no_grounding` cap） |

F01–F14 在 `grounding_available=false` 下 **MUST** 行為不變。

### 10.3 可選 live test

`M8_B_LIVE_GROUNDING=1` + 本機 `LOCAL_AI_API_URL`：**MAY** opt-in integration test（CI 預設 skip）。

---

## 11. Milestone 8-B 驗收

- [x] `system_grounding_settings` + Admin UI + seeder
- [x] `GroundingService` + 三 mode strategy
- [x] `local_llm_tool_loop` tool loop（web_search）
- [x] Auth + demo verification 注入 grounding metadata
- [x] Trust cap 調整 + F04/F15 測試
- [x] `Verification/Show` grounding 區塊（繁中）
- [x] `npm run typecheck`；全 suite 綠
- [x] **MUST NOT** 改 Cases 1–6（scorer 最小 cap diff 除外）

---

## Traceability

| 本文件章節 | 對應 |
|------------|------|
| §1 範圍 | [00-product-vision.md §3](00-product-vision.md)、[07-milestones.md §M8-B](07-milestones.md) |
| §2 欄位 | [02-contracts.md §2](02-contracts.md)、§10 |
| §3 Admin | [08-ui-auth-providers.md §4](08-ui-auth-providers.md) admin 模式 |
| §4 後端 | User D2、`.ai-dev/planning/m8-roadmap.md` |
| §9 Trust | [04-trust-level.md §3.1](04-trust-level.md)、F04/F15 |
| §10 測試 | [06-test-scenarios.md](06-test-scenarios.md) |
| §11 驗收 | M8-B Worker brief |

**技術決策**：Grounding 後端由 **Admin 可設定**；dev 預設 `local_llm_tool_loop` + `LOCAL_AI_API_URL`；Search API 為 production 選項。
