# 11 — Semantic Claim Alignment（Post-MVP · Milestone 8-C）

本文件定義 Milestone 8-C 的 **語意 claim 對齊**：在保留 MVP 字串對齊的前提下，以 Admin 可設定的 LLM 後端合併 **措辭不同但語意相同** 的 `canonical_key`，減少真模型路徑的 false negative（No Consensus）。

**前置**：M8-A、M8-B **RELEASED**。M8-C Worker **MUST** 先讀本文件、[07-milestones.md §M8-C](07-milestones.md)、Gate brief。

---

## 1. 範圍與邊界

### 1.1 M8-C 要做

| 領域 | 說明 |
|------|------|
| Admin 設定 | `system_aligner_settings` — mode、LLM endpoint、timeout |
| 對齊模組 | `app/Alignment/` — `ClaimAlignmentService` + semantic provider |
| 兩階段對齊 | 先 **字串對齊**（既有 `StringClaimAligner`）；`semantic_llm` mode 再跑 **key 語意聚類** |
| Audit metadata | `AlignmentResult.metadata` 記錄 mode、clusters、fallback |
| Fixture F16 | 語意 key 可對齊、字串不可對齊之 regression |
| 測試 | `M8CSemanticAlignmentTest`、F01–F15 字串 mode 回歸 |

### 1.2 M8-C MUST NOT

- 用 LLM **裁定 claim value 是否衝突**（value 比對仍走 [03-consensus-algorithm.md §5](03-consensus-algorithm.md) 確定性規則）
- 用 LLM 作 **唯一** consensus 裁決者
- 改寫 Cases 1–6（`HybridConsensusAnalyzer` 邏輯不變）
- 改寫 Trust cap（`CascadeTrustLevelScorer`）
- 在 `app/Consensus/` 直接呼叫 HTTP / Laravel AI SDK（走 `app/Alignment/` port）
- 完整 embedding 向量庫 / RAG pipeline（**MAY** 列為未來 `semantic_embedding` mode，M8-C **不實作**）
- 破壞 F01–F15 在 **`string` mode** 下行為

### 1.3 與 MVP / M8-B 的關係

- **預設 mode = `string`**：與 M1–M8-B 行為 **完全一致**
- **Semantic 僅合併 `canonical_key`**：合併後仍進入既有 Analyzer 衝突判準
- Grounding（M8-B）與 Alignment（M8-C）**獨立**：各自 Admin 設定、各自 metadata

---

## 2. 問題陳述

### 2.1 現況（`StringClaimAligner`）

```text
P1 canonical_key: "release date"
P2 canonical_key: "product launch date"
P3 canonical_key: "release date"
```

字串正規化後仍不相等 → P2 claim **unmatched** → 可能漏判衝突或錯�判 Full consensus。

### 2.2 目標（M8-C）

在 **同一 claim `type`** 內，若 LLM 判定兩個 `canonical_key` **語意等價**，則合併為同一 **aligned group**，再套用 §5 確定性 value 比對。

**保守原則**：不確定 **MUST NOT** 合併；寧可 unmatched，不可錯 merge。

---

## 3. Admin 設定 — `system_aligner_settings`

Singleton（與 `system_grounding_settings` 同模式）。

| 欄位 | 型別 | 說明 |
|------|------|------|
| `mode` | string | `string` \| `semantic_llm` |
| `enabled` | boolean | `false` 時等同 `string` |
| `local_api_url` | string | nullable；`semantic_llm` 時 OpenAI-compatible base URL |
| `local_model` | string | nullable |
| `local_api_key` | string | nullable；encrypted |
| `timeout_seconds` | int | 預設 15 |
| `min_confidence` | string | `high` \| `medium`；低於此 **MUST NOT** merge（預設 `high`） |

**Seeder 預設**：`mode=string`、`enabled=true`（**MUST NOT** 預設開 semantic，以免 CI/dev 意外依賴 LLM）。

---

## 4. 模組架構 — `app/Alignment/`

**MUST NOT** 在 `app/Consensus/Aligner/` 內呼叫 HTTP。

```text
ClaimAlignmentService (implements ClaimAligner)
    ├── StringClaimAligner（既有 · 第一階段）
    └── SemanticEquivalenceProvider（port）
            ├── NullSemanticEquivalenceProvider（string mode）
            └── LocalLlmSemanticEquivalenceProvider（semantic_llm）
```

### 4.1 `ClaimAlignmentService::align()`

1. `$base = StringClaimAligner->align($responses)`
2. 若 mode ≠ `semantic_llm` 或 disabled → 回傳 `$base`（metadata.mode=`string`）
3. 自 `$base` 的 **unmatched** claims 中，依 `type` 分組，收集待比對 key 配對
4. 呼叫 `SemanticEquivalenceProvider::clusterKeys()`（僅 `{boolean, date, number, version}` 四型 **SHOULD** 優先；`entity/source/statement` **MAY** skip）
5. 對 **high confidence** 等價 cluster：合併為 aligned entry（canonical_key 取 cluster 代表鍵）
6. 合併後 unmatched 更新；`AlignmentResult.metadata` 寫入 clusters / fallback

**失敗 fallback**：LLM timeout / invalid JSON / provider error → 回傳 `$base` + `metadata.fallback_reason`

### 4.2 `SemanticEquivalenceProvider` 契約

```php
/**
 * @param  array<int, array{type: string, provider: string, canonical_key: string, value: string, unit: ?string}>  $candidates
 * @return array{clusters: array<int, array{keys: string[], equivalent: bool, confidence: string}>, status: string}
 */
public function clusterKeys(array $candidates): array;
```

**LLM 輸出 MUST 為 JSON**（structured）；**MUST NOT** 要 LLM 比較 value 是否相等。

### 4.3 `LocalLlmSemanticEquivalenceProvider`

- OpenAI-compatible `POST /v1/chat/completions`
- System prompt：**僅**判斷 `canonical_key` 是否指涉同一 claim 維度（繁中/英文皆可）
- User payload：candidates JSON
- **MUST** 設 timeout；**MUST NOT** log API key

**範例 prompt 意圖**（Worker 可調整 wording，語意 MUST 保留）：

> 你是 claim key 對齊助手。只判斷 canonical_key 是否語意等價，不要判斷 value 對錯。輸出 JSON：`clusters[]` with `keys`, `equivalent`, `confidence`（high/medium/low）。

### 4.4 DI 綁定

`ConsensusServiceProvider` **MUST** 將 `ClaimAligner::class` 改綁 `ClaimAlignmentService::class`（內部注入 `StringClaimAligner` + provider resolver）。

---

## 5. 對齊規則（M8-C 擴充）

### 5.1 第一階段 — 字串（不變）

沿用 [03-consensus-algorithm.md §4](03-consensus-algorithm.md) 規則 1–5。

### 5.2 第二階段 — 語意 key 聚類（僅 `semantic_llm`）

1. 輸入：第一階段 **unmatched** claims（同 `type` 至少 2 個不同 provider 的 key 才送 LLM）
2. LLM 回傳 equivalent cluster → 合併為 aligned（providers 子 map 保留原 value/unit）
3. 合併後若 ≥2 provider → 進入 Analyzer §5 衝突判準
4. **MUST NOT** 合併不同 `type` 的 claims
5. **MUST NOT** 合併 `confidence=low` 或低於 `min_confidence` 的 cluster

### 5.3 `AlignmentResult.metadata`（建議欄位）

| 鍵 | 說明 |
|----|------|
| `aligner_mode` | `string` \| `semantic_llm` |
| `semantic_clusters` | LLM 回傳摘要（不含 prompt / key） |
| `fallback_reason` | nullable |
| `semantic_skipped` | bool — 無 candidates 時 |

---

## 6. HTTP 與 UI

### 6.1 Admin

| 方法 | 路徑 | 說明 |
|------|------|------|
| `GET` | `/admin/aligner` | 讀取設定（key 以 `has_local_api_key` 曝露） |
| `PUT` | `/admin/aligner` | 更新 mode / URL / timeout |

`AdminAlignerController`；繁中 UI `Pages/Admin/Aligner.vue`（或併入 Admin 子區）。

### 6.2 Verification Show（可選）

**MAY** 顯示 `aligner_mode` / semantic fallback badge（繁中）；**MUST NOT** 顯示 raw API key。

---

## 7. 與 Consensus Workflow 整合

- `ConsensusWorkflow` **MUST NOT** 改簽名；仍 `$this->aligner->align($analyzable)`
- alignment payload 持久化至 `consensus_results.alignment` **SHOULD** 含 metadata（既有 JSON 欄位擴充）
- Fake fixture / replay **MUST** 在 `string` mode 下與 F01–F15 一致

---

## 8. Fixture F16（新增）

| 項目 | 值 |
|------|-----|
| 名稱 | F16 — Semantic Key Alignment |
| 分類 | Type B, **open** |
| 輸入 | 三方 analyzable；`date` claim **value 相同**；canonical_key：`release date` / `product launch date` / `official launch date` |
| `string` mode | keys 不配對 → 多個 unmatched → 可能 **Full (low-discriminability)** 或 open Full（視 claims 結構） |
| `semantic_llm` mode（mock provider） | keys 合併 → aligned date → **無 major 衝突** → `Full` |
| Trust | `High`（與 F08 類似 open Full 路徑） |
| Minority Report | 否 |

**F08 釐清**（更新 [06-test-scenarios.md](06-test-scenarios.md)）：

- F08 仍測 **extractor** 產出 **一致** canonical_key 之路徑
- F16 測 **aligner 語意** 能力；兩者互補

---

## 9. 測試

### 9.1 `M8CSemanticAlignmentTest.php`

- Admin 可更新 settings；非 admin 403
- `string` mode → 行為等同直接 `StringClaimAligner`
- mock `SemanticEquivalenceProvider` → F16 路徑 aligned
- LLM failure → fallback string result + `fallback_reason`
- encrypted key 不回傳 raw value

### 9.2 回歸

- F01–F15：**string mode** 全綠（現有 unit + feature tests）
- F08 語意不變
- `npm run typecheck`；全 suite 綠

### 9.3 可選 live test

`M8_C_LIVE_SEMANTIC=1` + 本機 LLM：**MAY** opt-in（CI 預設 skip）。

---

## 10. Milestone 8-C 驗收

- [ ] `system_aligner_settings` + Admin UI + seeder（預設 `string`）
- [ ] `app/Alignment/` — `ClaimAlignmentService` + semantic provider
- [ ] `ClaimAligner` DI 改綁 service；`StringClaimAligner` 保留
- [ ] F16 fixture + `M8CSemanticAlignmentTest`
- [ ] F01–F15 string mode 回歸
- [ ] **MUST NOT** 改 Cases 1–6 / Trust caps
- [ ] Show **MAY** 顯示 aligner metadata（繁中）

---

## Traceability

| 本文件章節 | 對應 |
|------------|------|
| §1 範圍 | [07-milestones.md §M8-C](07-milestones.md)、[m8-roadmap.md §M8-C](../.ai-dev/planning/m8-roadmap.md) |
| §3 Admin | [08-ui-auth-providers.md §4](08-ui-auth-providers.md) admin 模式 |
| §4 模組 | [01-architecture.md §4](01-architecture.md) domain 邊界 |
| §5 規則 | [03-consensus-algorithm.md §4](03-consensus-algorithm.md) |
| §7 canonical_key | [02-contracts.md §7](02-contracts.md) |
| §8 F16 | [06-test-scenarios.md](06-test-scenarios.md) |
| §9–10 測試 | M8-C Worker brief |

**技術決策**：M8-C v1 採 **Admin 可選 `semantic_llm`**（本機 OpenAI-compatible LLM）；**預設 `string`**；失敗 **fallback** 字串對齊；不做 embedding 向量庫。
