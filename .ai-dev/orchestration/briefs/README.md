# Worker 派工（Gate Briefs）

每個 Gate 一個**目錄**，內含固定兩份文件：

| 文件 | 用途 |
|------|------|
| [brief.md](M2-A/brief.md) | 派工說明（角色、必讀、MUST NOT、建議做法） |
| [progress.md](M2-A/progress.md) | **可勾選交付清單 + 驗收輸出 + 審核紀錄**（Worker 填 §1–4，Orchestrator 填 §5） |

前置 Gate 未 **RELEASED** 前不得開工下一 Gate。總狀態見 [gate-status.md](../gate-status.md)。

---

## Milestone 2 — Laravel Skeleton ✅

M2-A … M2-E **RELEASED**（2026-06-13）

---

## Milestone 3 — Provider Integration ✅

| Gate | 目錄 | 狀態 |
|------|------|------|
| M3-A | [M3-A/](M3-A/) | **RELEASED** |
| M3-B | [M3-B/](M3-B/) | **RELEASED** |

**Milestone 3**：**RELEASED**（2026-06-13）

---

## Milestone 4 — Consensus Engine（3 Gate）

> 2026-06-13 自原 6 Gate 精簡：A 輸入、B 核心、C 報告+fixtures。

| Gate | 目錄 | 狀態 | 說明 |
|------|------|------|------|
| **M4-A** | [M4-A/](M4-A/) | **OPEN** | Classifier + Extractor + CT-G |
| M4-B | [M4-B/](M4-B/) | BLOCKED | Aligner + Analyzer + Trust |
| M4-C | [M4-C/](M4-C/) | BLOCKED | Verdict + F01–F14 |

---

## 派工入口

**現在派 M4-A**：將 [M4-A/brief.md](M4-A/brief.md) 與 [M4-A/progress.md](M4-A/progress.md) 一併交給 Worker。

交還 Orchestrator 時：

```markdown
## Gate: M4-A
## progress.md
（附連結或 diff：progress 已勾選 §1、§2 已貼輸出）
## 備註
```
