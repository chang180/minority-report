# Gate 放行狀態

> Orchestrator 維護。每個 Gate 只有 Orchestrator 審核整合後可標為 **RELEASED**。  
> **實作階段**：使用者只派 Worker；審核僅 Orchestrator。  
> **M1 spec**：Claude 全文審查已整合（2026-06-13 patch：04 cap 雙列、CT-G fail-safe）。

**當前 Milestone**：M3 — Provider Integration  
**當前可開工 Gate**：**M3-B**（M3-A 已 RELEASED）

---

## Milestone 1 — Spec Documents

| Gate | 說明 | 狀態 | 放行日 |
|------|------|------|--------|
| M1 | docs/00..07 + plan + handoff | **RELEASED** | 2026-06-13 |

---

## Milestone 2 — Laravel Skeleton

| Gate | 說明 | 狀態 | 備註 |
|------|------|------|------|
| M2-A | Laravel 13 專案初始化 | **RELEASED** | 2026-06-13 · [M2-A/](briefs/M2-A/) |
| M2-B | Consensus 目錄 + interface/DTO 骨架 | **RELEASED** | 2026-06-13 · [M2-B/](briefs/M2-B/) |
| M2-C | config/consensus.php + DI wiring | **RELEASED** | 2026-06-13 · [M2-C/](briefs/M2-C/) |
| M2-D | migrations + model skeleton | **RELEASED** | 2026-06-13 · [M2-D/](briefs/M2-D/) |
| M2-E | routes + M2 驗收 | **RELEASED** | 2026-06-13 · [M2-E/](briefs/M2-E/) |

**Milestone 2**：**RELEASED**（2026-06-13）

---

## Milestone 3 — Provider Integration

> **2026-06-13 精簡**：原 M3-A～D（4 Gate）合併為 **M3-A + M3-B**（fake+編排 / 真 adapter 各一 Gate）。

| Gate | 說明 | 狀態 |
|------|------|------|
| **M3-A** | fake provider + F01 replay + raw answer 編排 | **RELEASED** | 2026-06-13 · [M3-A/](briefs/M3-A/) |
| **M3-B** | Laravel AI SDK adapter（OpenAI + Claude + Gemini） | **OPEN** | [M3-B/](briefs/M3-B/) |

---

## Milestone 4 — Consensus Engine

| Gate | 狀態 |
|------|------|
| M4-A … M4-F | BLOCKED（待 M3 RELEASED） |

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

## 放行紀錄格式

```text
YYYY-MM-DD | M2-A | RELEASED | Orchestrator 整合 Review #1,#3；對齊 01-architecture
```

（正式紀錄於下方）

### 紀錄

- 2026-06-13 | M1 | RELEASED | Spec 建立；Cross-Review T1–T3 + T2-G
- 2026-06-13 | M1 | PATCH | Claude 審查：04 §2 有效表態 cap 獨立列；06 CT-G1–G3；description §15 同步
- 2026-06-13 | — | REORG | briefs 改為每 Gate 目錄 + progress.md
- 2026-06-13 | — | POLICY | M2+：`docs/`、根 README 僅 Orchestrator 可改；Worker 寫 progress §4 建議
- 2026-06-13 | M2-A | RELEASED | Laravel 13 + SQLite + Boost + Vue/Inertia/TS + Pest + CI
- 2026-06-13 | M2-A | PATCH | Lead：`laravel/ai` 納入 M2-A 交付
- 2026-06-13 | M2-B | RELEASED | `app/Consensus/*` Contracts+DTO 骨架
- 2026-06-13 | M2-C | RELEASED | `config/consensus.php` + ConsensusServiceProvider + Null stubs DI
- 2026-06-13 | M2-D | RELEASED | audit 三表 migration + models
- 2026-06-13 | M2-E | RELEASED | `GET /health` + HealthCheckTest；M2 milestone 完成
- 2026-06-13 | — | PLAN | M3 Gate 自 4 個精簡為 2 個（A fake+編排、B 真 adapter）
- 2026-06-13 | M3-A | RELEASED | FakeLlmProvider + InMemoryFakeProviderRegistry + ProviderOrchestrator + Eloquent persistence；F01 replay
