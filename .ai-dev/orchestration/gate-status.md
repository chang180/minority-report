# Gate 放行狀態

> Orchestrator 維護。每個 Gate 只有 Orchestrator 審核整合後可標為 **RELEASED**。  
> **實作階段**：使用者只派 Worker；審核僅 Orchestrator。  
> **M1 spec**：Claude 全文審查已整合（2026-06-13 patch：04 cap 雙列、CT-G fail-safe）。

**當前 Milestone**：M4 — Consensus Engine  
**當前可開工 Gate**：**M4-C**（M4-B 已 RELEASED）

---

## Milestone 1 — Spec Documents

| Gate | 說明 | 狀態 | 放行日 |
|------|------|------|--------|
| M1 | docs/00..07 + plan + handoff | **RELEASED** | 2026-06-13 |

---

## Milestone 2 — Laravel Skeleton

| Gate | 說明 | 狀態 | 備註 |
|------|------|------|------|
| M2-A … M2-E | 見 briefs/M2-* | **RELEASED** | 2026-06-13 |

**Milestone 2**：**RELEASED**（2026-06-13）

---

## Milestone 3 — Provider Integration

| Gate | 說明 | 狀態 | 備註 |
|------|------|------|------|
| M3-A | fake provider + raw answer 編排 | **RELEASED** | 2026-06-13 · [M3-A/](briefs/M3-A/) |
| M3-B | Laravel AI SDK adapter（OpenAI + Claude + Gemini） | **RELEASED** | 2026-06-13 · [M3-B/](briefs/M3-B/) |

**Milestone 3**：**RELEASED**（2026-06-13）

---

## Milestone 4 — Consensus Engine

> **2026-06-13 精簡**：原 M4-A～F（6 Gate）合併為 **M4-A + M4-B + M4-C**。

| Gate | 說明 | 狀態 |
|------|------|------|
| **M4-A** | Question Classifier + Response Extractor（含 CT-G1–G3） | **RELEASED** | 2026-06-13 · [M4-A/](briefs/M4-A/) |
| **M4-B** | Claim Aligner + Consensus Analyzer + Trust Level Scorer | **RELEASED** | 2026-06-13 · [M4-B/](briefs/M4-B/) |
| **M4-C** | Verdict Reporter + Fixture F01–F14 整合驗收 | **OPEN** | [M4-C/](briefs/M4-C/) |

---

## Milestone 5 — Audit Trail

| Gate | 狀態 |
|------|------|
| M5-A … M5-B | BLOCKED（待 M4 RELEASED） |

---

## Milestone 6 — Minimal UI

| Gate | 狀態 |
|------|------|
| M6-A … M6-B | BLOCKED（待 M5 RELEASED） |

---

## 放行紀錄

- 2026-06-13 | M1 | RELEASED | Spec 建立
- 2026-06-13 | M2-A … M2-E | RELEASED | Laravel skeleton 完成
- 2026-06-13 | — | PLAN | M3 Gate 自 4 個精簡為 2 個
- 2026-06-13 | M3-A | RELEASED | fake provider + ProviderOrchestrator + persistence
- 2026-06-13 | M3-B | RELEASED | `app/AI/Providers/*` SDK adapters + ConfiguredLlmProviderFactory
- 2026-06-13 | — | PLAN | M4 Gate 自 6 個精簡為 3 個（A 輸入、B 核心、C 報告+fixtures）
- 2026-06-13 | M4-A | RELEASED | FailSafeQuestionClassifier + JsonResponseExtractor + ResponseExtractionOrchestrator；CT-G1–G3
- 2026-06-13 | M4-B | RELEASED | StringClaimAligner + HybridConsensusAnalyzer + CascadeTrustLevelScorer；Cases 1–6 + trust table
