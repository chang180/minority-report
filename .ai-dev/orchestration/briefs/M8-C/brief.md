# Worker Brief — Gate M8-C

**Milestone 8 · Semantic Claim Alignment**  
**前置**：M8-A **RELEASED** · M8-B **RELEASED**  
**狀態**：**可開工**（2026-06-14）

> M8-C 為 Milestone 8 **最後 Gate**。只改 **aligner 層** + Admin 設定；Cases 1–6、Trust、Grounding **MUST NOT** 改動。

---

## 角色

Worker Agent。**只做 M8-C**：語意 key 對齊、Admin 設定、F16、測試。

---

## 必讀

1. **[docs/11-semantic-alignment.md](../../../../docs/11-semantic-alignment.md)**（全文）
2. [docs/03-consensus-algorithm.md §4](../../../../docs/03-consensus-algorithm.md)（字串對齊基線）
3. 現有：`StringClaimAligner`、`ConsensusServiceProvider`、`AdminGroundingController`（Admin 模式參考）
4. 本 brief · [progress.md](progress.md)

---

## 背景

| 已有 | 缺口 |
|------|------|
| `StringClaimAligner` 字串正規化 | 同義不同 key → unmatched → false No Consensus |
| `ClaimAligner` DI → `StringClaimAligner` | 無 Admin 可切換 |
| F08 測 extractor key 一致 | 無 F16 語意對齊 fixture |
| M8-B Admin 設定模式 | 可複製至 aligner |

---

## 交付物

### 1. 資料庫與 Models

- [ ] Migration + Model：`system_aligner_settings`（singleton；見 11 §3）
- [ ] `encrypted` cast：`local_api_key`
- [ ] Seeder：**預設 `mode=string`**（CI 不依賴 LLM）

### 2. 模組 — `app/Alignment/`

**MUST NOT** 在 `app/Consensus/` 呼叫 HTTP / AI SDK。

- [ ] `Contracts/SemanticEquivalenceProvider`
- [ ] `Providers/NullSemanticEquivalenceProvider`
- [ ] `Providers/LocalLlmSemanticEquivalenceProvider`（JSON structured output）
- [ ] `ClaimAlignmentService` implements `ClaimAligner`
- [ ] 內部使用既有 `StringClaimAligner` 作第一階段
- [ ] 失敗 fallback → 字串結果 + `metadata.fallback_reason`

### 3. DI

- [ ] `ConsensusServiceProvider`：`ClaimAligner::class` → `ClaimAlignmentService::class`

### 4. HTTP + UI

- [ ] `AdminAlignerController` — `GET/PUT /admin/aligner`
- [ ] `Pages/Admin/Aligner.vue`（繁中）
- [ ] Show.vue **MAY** 顯示 aligner_mode badge

### 5. Fixture + Fake

- [ ] F16 fixture catalog entry（或 fake provider 場景）
- [ ] 測試可 mock `SemanticEquivalenceProvider`

### 6. Feature Tests

- [ ] `M8CSemanticAlignmentTest.php`
- [ ] F01–F15 **string mode** 回歸（現有 tests 全綠）

---

## 驗收命令

```text
npm run typecheck
vendor/bin/pint --dirty --format agent
php artisan test --compact
php artisan test --filter=M8C
```

---

## MUST NOT

- 改 `HybridConsensusAnalyzer` Cases 1–6
- 改 `CascadeTrustLevelScorer` caps
- 改 Grounding（M8-B）
- 用 LLM 判斷 claim **value** 衝突
- embedding 向量庫
- 修改 `docs/`、根 `README.md`
- 預設 seeder 開 `semantic_llm`（必須 `string`）

---

## 完成後交還

1. 更新 [progress.md](progress.md) §1–4
2. §4 列「建議 Orchestrator 文件更新」
3. 使用者轉交 Orchestrator **審核 RELEASED**

---

## 建議實作順序

```text
1. system_aligner_settings + seeder (string default)
2. ClaimAlignmentService + string-only path (DI swap, 回歸 F01–F15)
3. LocalLlmSemanticEquivalenceProvider + merge logic
4. Admin API + UI
5. F16 + M8CSemanticAlignmentTest
6. Full suite + typecheck
```
