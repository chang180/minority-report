# 關鍵報告（Minority Report）— Agent Handoff

> **當前階段**：Milestone 5 — Audit Trail（M4 ✅）。  
> **下一步**：Gate **M5-A** — replay + audit 完整性（見 [briefs/M5-A/](briefs/M5-A/)）。

「關鍵報告」是一套 Multi-LLM Consensus Engine：降低單一模型幻覺風險，揭露多模型間的共識、分歧與不確定性。

**決策來源**：[decisions/description.md](../decisions/description.md)（共識算法等拍板決策）  
**正式規格**：`docs/00..07`（實作 MUST 以 spec 為準）  
**分階段計畫**：[planning/plan.md](../planning/plan.md)  
**Orchestrator**：[orchestrator.md](orchestrator.md) · Gate：[gate-status.md](gate-status.md) · **派工**：[briefs/](briefs/)

---

## Spec 索引

| 文件 | 摘要 |
|------|------|
| [00-product-vision.md](../../docs/00-product-vision.md) | 願景、哲學、MVP 邊界、Non Goals |
| [01-architecture.md](../../docs/01-architecture.md) | Laravel 13 架構、模組邊界、延遲策略 |
| [02-contracts.md](../../docs/02-contracts.md) | DTO、狀態分離、Interface、Audit 欄位（**術語 canonical**） |
| [03-consensus-algorithm.md](../../docs/03-consensus-algorithm.md) | 對齊、衝突、Cases 1–6、Minority Report |
| [04-trust-level.md](../../docs/04-trust-level.md) | base + caps 瀑布、decision table |
| [05-failure-modes.md](../../docs/05-failure-modes.md) | Provider/Extractor 失敗、3/3–0/3 狀態機 |
| [06-test-scenarios.md](../../docs/06-test-scenarios.md) | Fixture F01–F14、CT-G 測試 |
| [07-milestones.md](../../docs/07-milestones.md) | M1–M6 拆解與驗收 |

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

## 文件編輯權（M2+）

| 路徑 | Worker | Orchestrator |
|------|--------|--------------|
| `docs/`、`docs/README.md` | **MUST NOT** 修改（唯讀） | **唯一**可改動者 |
| 根目錄 `README.md` | **MUST NOT** 修改 | **唯一**可改動者 |
| progress §4 | **MUST** 列「建議 Orchestrator 文件更新」 | 放行前整合進 docs / README |

實作與 spec 不一致、或需補 Development 步驟時，**由 Orchestrator 回寫**，不在 Worker diff 中出現上述路徑。

---

## Milestone 2（Gate 制）

| Gate | 派工文件 |
|------|----------|
| **M2-A**（RELEASED） | [briefs/M2-A/](briefs/M2-A/)（brief + progress） |
| **M2-B**（RELEASED） | [briefs/M2-B/](briefs/M2-B/) |
| **M2-C**（RELEASED） | [briefs/M2-C/](briefs/M2-C/) |
| **M2-D**（RELEASED） | [briefs/M2-D/](briefs/M2-D/) |
| **M2-E**（RELEASED） | [briefs/M2-E/](briefs/M2-E/) |

**M2 已完成。** M2 完成前 MUST NOT 實作 consensus 判定邏輯（屬 M4）。

---

## Milestone 3（Gate 制 · 2 Gate）

| Gate | 派工文件 |
|------|----------|
| **M3-A**（RELEASED） | [briefs/M3-A/](briefs/M3-A/) |
| **M3-B**（RELEASED） | [briefs/M3-B/](briefs/M3-B/) |

**M3 已完成。** M3 完成前 MUST NOT 實作 consensus 判定邏輯；判定邏輯自 **M4** 起。

---

## Milestone 4（Gate 制 · 3 Gate）✅

M4-A … M4-C **RELEASED**（2026-06-13）。Consensus 核心流程（Classifier → Verdict）可跑，F01–F14 通過。

---

## Milestone 5（Gate 制 · 1 Gate）

| Gate | 派工文件 |
|------|----------|
| **M5-A**（OPEN） | [briefs/M5-A/](briefs/M5-A/) — replay + §10 audit 完整性 |

---

## Agent 工作方式

### 必讀

1. 本文件 + [02-contracts.md](../../docs/02-contracts.md) + 當前 Gate 的 [briefs/](briefs/)
2. Infra：[01-architecture.md](../../docs/01-architecture.md)

### 協作流程（M2+）

1. 使用者依 [briefs/](briefs/) 派 Worker（**brief.md + progress.md**）
2. Worker 實作 → **更新該 Gate 的 progress.md** → 使用者轉交 Orchestrator
3. Orchestrator 審核 progress + 程式碼 → **必要時更新 docs/、根 README** → 更新 [gate-status.md](gate-status.md) → 放行 → 下一 Gate

---

## 文件沿革

| 版本 | 說明 |
|------|------|
| decisions/description.md | spec 前決策 handoff |
| docs/00..07 | M1 正式 spec |
| 本 handoff.md | M2+ 交接 |
| orchestration/briefs/ | Worker 派工 |
