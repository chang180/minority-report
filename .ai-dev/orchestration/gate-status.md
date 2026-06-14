# Gate 放行狀態

> Orchestrator 維護。每個 Gate 只有 Orchestrator 審核整合後可標為 **RELEASED**。

**當前 Milestone**：M6 — Minimal UI  
**當前可開工 Gate**：**M6-A**（M5 已 RELEASED）

---

## Milestone 1–4

M1–M4 全 **RELEASED**（2026-06-13）

---

## Milestone 5 — Audit Trail

| Gate | 說明 | 狀態 | 備註 |
|------|------|------|------|
| M5-A | replay + audit §10 完整性 | **RELEASED** | 2026-06-14 · [M5-A/](briefs/M5-A/) |

**Milestone 5**：**RELEASED**（2026-06-14）

---

## Milestone 6 — Minimal UI

> **2026-06-14 精簡**：原 M6-A/B 合併為 **M6-A**（問題輸入 + 結果頁一次交付）。

| Gate | 說明 | 狀態 |
|------|------|------|
| **M6-A** | 問題輸入 + 驗證結果 UI（Vue/Inertia） | **OPEN** |

---

## 放行紀錄

- 2026-06-13 | M4 | RELEASED | Consensus 引擎 + F01–F14
- 2026-06-13 | — | PLAN | M5 合併為 M5-A
- 2026-06-14 | M5-A | RELEASED | ConsensusReplayService + auditTrailForRequest + replayFromPersisted
- 2026-06-14 | M5 | RELEASED | Milestone 5 完成
- 2026-06-14 | — | PLAN | M6 合併為 M6-A
