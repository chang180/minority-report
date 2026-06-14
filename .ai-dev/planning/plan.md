# 關鍵報告 — Docs 規格書分階段計畫

## 現況

- 決策與協作文件見 [.ai-dev/](../.ai-dev/README.md)（`decisions/`、`planning/`、`orchestration/`）
- 依 §0、§21 Milestone 1：**必須先完成 spec，不得寫 Laravel application code**

## 技術決策更新（優先於 description.md §6）

| 項目 | description.md | 本專案採用 |
|------|----------------|------------|
| Framework | Laravel 12+ | **Laravel 13**（最新版，直接採用） |
| PHP | 8.4+ | 8.4+（不變） |

撰寫 spec 時：`01-architecture.md`、`07-milestones.md` 與 `handoff.md` 一律寫 **Laravel 13**；若與 `description.md` §6 不一致，以本表為準，並在 spec 末尾 Traceability 註記此覆寫。

## 目標產出

```text
docs/
├── 00-product-vision.md
├── 01-architecture.md
├── 02-contracts.md
├── 03-consensus-algorithm.md
├── 04-trust-level.md
├── 05-failure-modes.md
├── 06-test-scenarios.md
└── 07-milestones.md

.ai-dev/
├── README.md
├── decisions/description.md
├── planning/plan.md
└── orchestration/
    ├── handoff.md
    ├── orchestrator.md
    ├── gate-status.md
    └── briefs/              ← Worker 派工（M2-A…E）
```

## 文件依賴與撰寫順序

[`decisions/description.md` §23](../decisions/description.md) 已拍板優先順序；以下加入**可並行**與**審核閘門**：

1. `02-contracts.md`（基礎）
2. `03-consensus-algorithm.md` + `05-failure-modes.md`（並行）
3. `04-trust-level.md` → `06-test-scenarios.md`
4. `00-product-vision.md` + `01-architecture.md`
5. `07-milestones.md` → `handoff.md`

---

## Phase 0 — 專案骨架與計畫落地

**交付物**：`docs/` 目錄 + `plan.md` + `docs/README.md`（撰寫規範與術語表）

## Phase 1 — 核心契約

**文件**：`docs/02-contracts.md`

## Phase 2 — 共識演算法 + 失敗模式

**文件**：`docs/03-consensus-algorithm.md`、`docs/05-failure-modes.md`

## Phase 3 — Trust Level + 測試場景

**文件**：`docs/04-trust-level.md`、`docs/06-test-scenarios.md`

## Phase 4 — 架構與產品願景

**文件**：`docs/00-product-vision.md`、`docs/01-architecture.md`

## Phase 5 — 里程碑整合、交叉審核、handoff 重建

**文件**：`docs/07-milestones.md`、`.ai-dev/orchestration/handoff.md`

---

## Agent 分工建議

| Agent | 負責 | 前置 | 產出 |
|-------|------|------|------|
| Lead | Phase 0, 5, 全體審核 | — | plan.md, 07, handoff.md |
| Agent A | Phase 1 | Phase 0 | 02-contracts.md |
| Agent B | Phase 2A | 02 審核通過 | 03-consensus-algorithm.md |
| Agent C | Phase 2B | 02 審核通過 | 05-failure-modes.md |
| Agent D | Phase 3A | 03 審核通過 | 04-trust-level.md |
| Agent E | Phase 3B | 03+04+05 | 06-test-scenarios.md |
| Agent F | Phase 4 | 02 審核通過 | 00 + 01 |

---

## Phase 6+ — 實作 Orchestration（M2–M6）

M1 已完成。自 Milestone 2 起採 **Gate 制 + Orchestrator 整合**：

```text
Orchestrator 發 Brief → Worker 產出 → User 轉交 Orchestrator 審核
    → 整合 + 對齊 spec → 更新 gate-status → 下一 Gate
```

| 文件 | 用途 |
|------|------|
| [orchestration/orchestrator.md](../orchestration/orchestrator.md) | Lead 手冊、Gate 切分 |
| [orchestration/gate-status.md](../orchestration/gate-status.md) | 放行狀態 |
| [orchestration/briefs/](../orchestration/briefs/) | Worker 派工 |

M3–M6 詳見 orchestrator.md §5。M2 已完成；**M3 改 2 Gate**（A fake+編排、B adapter）。

### M3 Gate 摘要（2026-06-13 精簡）

| Gate | 交付物 |
|------|--------|
| M3-A | fake `LlmProvider` + F01 + 並行 raw answer + 持久化 |
| M3-B | `app/AI/Providers/*` + OpenAI / Claude / Gemini |

### M4 Gate 摘要（2026-06-13 精簡 · 3 Gate）

| Gate | 交付物 |
|------|--------|
| M4-A | Question Classifier + Response Extractor + CT-G1–G3 |
| M4-B | Claim Aligner + Consensus Analyzer + Trust Level Scorer |
| M4-C | Verdict Reporter + Fixture F01–F14 整合驗收 |

### M5 Gate 摘要（2026-06-13 精簡 · 1 Gate）

| Gate | 交付物 |
|------|--------|
| M5-A | replay mechanism + audit §10 完整性驗收 |

### M6 Gate 摘要（2026-06-14 精簡 · 1 Gate）

| Gate | 交付物 |
|------|--------|
| M6-A | 問題輸入 + 驗證結果 UI（Vue/Inertia） |

### M2 Gate 摘要（已完成）

| Gate | 交付物 |
|------|--------|
| M2-A | Laravel 13 專案 + `.env.example` |
| M2-B | `app/Consensus/` interface/DTO 骨架 |
| M2-C | `config/consensus.php` + DI |
| M2-D | audit migrations + models |
| M2-E | routes + M2 驗收 |

---

## 風險與緩解

| 風險 | 緩解 |
|------|------|
| 多 agent 並行導致術語不一致 | Phase 0 定義術語表；02 為 canonical |
| Trust decision table 遺漏 cap 組合 | 04 要求 exhaustive matrix + 06 逐 Fixture 驗算 |
| 01 的 provider 選型未定 | spec 寫「評估標準 + 占位決策」，Milestone 2 前再確認 |
| description 與 spec 漂移 | 每份 spec 末尾 Traceability + Phase 5 交叉審核 |
