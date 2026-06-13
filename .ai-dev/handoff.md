# 關鍵報告（Minority Report）— Agent Handoff

> **當前階段**：Milestone 1 完成 — Spec Documents 已就緒。  
> **下一步**：Milestone 2 — Laravel 13 Skeleton（見 [docs/07-milestones.md](../docs/07-milestones.md)）。

「關鍵報告」是一套 Multi-LLM Consensus Engine：降低單一模型幻覺風險，揭露多模型間的共識、分歧與不確定性。

**決策來源**：[.ai-dev/description.md](description.md)（共識算法等拍板決策）  
**正式規格**：`docs/00..07`（實作 MUST 以 spec 為準）  
**分階段計畫**：[.ai-dev/plan.md](plan.md)  
**Orchestrator 流程**：[.ai-dev/orchestrator.md](orchestrator.md) · 當前 Gate：[gate-status.md](gate-status.md)

---

## Spec 索引

| 文件 | 摘要 |
|------|------|
| [00-product-vision.md](../docs/00-product-vision.md) | 願景、哲學、MVP 邊界、Non Goals |
| [01-architecture.md](../docs/01-architecture.md) | Laravel 13 架構、模組邊界、延遲策略 |
| [02-contracts.md](../docs/02-contracts.md) | DTO、狀態分離、Interface、Audit 欄位（**術語 canonical**） |
| [03-consensus-algorithm.md](../docs/03-consensus-algorithm.md) | 對齊、衝突、Cases 1–6、Minority Report |
| [04-trust-level.md](../docs/04-trust-level.md) | base + caps 瀑布、decision table |
| [05-failure-modes.md](../docs/05-failure-modes.md) | Provider/Extractor 失敗、3/3–0/3 狀態機 |
| [06-test-scenarios.md](../docs/06-test-scenarios.md) | Fixture F01–F14、Success Criteria |
| [07-milestones.md](../docs/07-milestones.md) | M1–M6 拆解與驗收 |

---

## 技術決策（優先於 description §6）

| 項目 | 採用 |
|------|------|
| Framework | **Laravel 13** |
| PHP | 8.4+ |
| DB | SQLite（MVP）或 MySQL |

---

## 不可違反的硬性規則 Top 10

1. **MUST NOT** 用單一 LLM Judge 作唯一裁決者。
2. **MUST NOT** 把多家答案餵進同一次 Extractor 呼叫（逐 provider 獨立抽取）。
3. **MUST NOT** 混用 `provider_status` 與 `extraction_status`（禁止含糊 `invalid_response`）。
4. **MUST NOT** 輸出百分比 Trust Score；只用 High/Medium/Low/Unknown + base/caps 瀑布。
5. **MUST NOT** 用 open 題的 `direct_answer` 投票；open 題填 `not_applicable`。
6. **MUST NOT** 把 `direct_answer = unknown` 當反對票或少數意見（棄權，排除計票）。
7. **MUST NOT** 讓 Insufficient（==1）與 Failure（==0）條件重疊。
8. **MUST NOT** 在多軸衝突指向不同 provider 時判 Majority（改判 None）。
9. **MUST NOT** 對 Type C 無 grounding 問題給 High Trust。
10. **MUST NOT** 在 MVP 引入 Phase 3 的 grounding、語意對齊或完整 RAG。

---

## Milestone 2 待辦（Laravel 13 Skeleton）

- [ ] `composer create-project` Laravel 13
- [ ] 建立 `app/Consensus/{Contracts,DTO,Classifier,Extractor,Aligner,Analyzer,Scorer,Reporter}`
- [ ] 建立 `app/AI/Providers/`
- [ ] config `consensus.php`（number conflict threshold 等）
- [ ] migration skeleton：verification_requests、provider_responses、consensus_results
- [ ] `.env.example`：三 provider API keys（可空，配合 fake provider）

**M2 完成前 MUST NOT 實作 consensus 判定邏輯**（屬 M4）。  
**M2 不要求** Provider SDK PoC；AI 介面化在 M3 以 Laravel AI SDK adapter 完成。

---

## Agent 工作方式

### 開始實作前必讀

1. 本文件 + [02-contracts.md](../docs/02-contracts.md) + [03-consensus-algorithm.md](../docs/03-consensus-algorithm.md)
2. 若做 Trust / 測試：[04-trust-level.md](../docs/04-trust-level.md) + [06-test-scenarios.md](../docs/06-test-scenarios.md)
3. 若做 infra：[01-architecture.md](../docs/01-architecture.md)

### 開發順序

```text
fake provider (M3) → Fixture tests (M4) → 真 API adapter (M3) → Audit (M5) → UI (M6)
```

### 協作流程

**Spec 審查（M1）**：Claude 審查已整合（2026-06-13）；M1 spec 定案，不再另開審查。

**實作（M2+）**：

1. Orchestrator 發 Gate Brief
2. 你派 Worker Agent
3. Worker 產出 → **你轉交 Orchestrator**（不需另開審查 AI）
4. Orchestrator 審核、整合、更新 [gate-status.md](gate-status.md) → 放行 → 下一 Brief

Worker 產出 **不得** 直接 merge；**僅 Orchestrator 放行**。

---

## 當前 Gate：M2-A

見 [gate-status.md](gate-status.md)。對 Orchestrator 說「發 M2-A brief」可取得 Worker 派工文本。

---

## 文件沿革

| 版本 | 說明 |
|------|------|
| description.md | spec 前決策 handoff（1074 行，不修改語意） |
| docs/00..07 | Milestone 1 正式 spec（本輪產出） |
| 本 handoff.md | M1 完成後給 M2+ agent 的精簡交接 |
