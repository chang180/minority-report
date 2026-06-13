# Worker 派工（Gate Briefs）

每個 Gate 一個**目錄**，內含固定兩份文件：

| 文件 | 用途 |
|------|------|
| [brief.md](M2-A/brief.md) | 派工說明（角色、必讀、MUST NOT、建議做法） |
| [progress.md](M2-A/progress.md) | **可勾選交付清單 + 驗收輸出 + 審核紀錄**（Worker 填 §1–4，Orchestrator 填 §5） |

前置 Gate 未 **RELEASED** 前不得開工下一 Gate。總狀態見 [gate-status.md](../gate-status.md)。

---

## 流程

```text
讀 brief.md → 實作 → 更新 progress.md（勾選 + 貼驗收輸出）
    → 使用者轉交 Orchestrator → 審核 progress.md + 程式碼 → RELEASED
```

**Orchestrator 只認 `progress.md` 的勾選與證據**，不以口頭摘要放行。

**文件**：`docs/` 與根 `README.md` **僅 Orchestrator 可改**；Worker 若有回寫需求，寫在 progress **§4 建議 Orchestrator 文件更新**。

### Laravel AI SDK（`laravel/ai`）政策

| 時機 | 允許 | 禁止 |
|------|------|------|
| **M2-A**（Lead 選項 · 本 repo 已採用） | `composer require laravel/ai`、publish `config/ai.php`、migrate、Boost `ai-sdk-development` | 在 `app/Consensus/` 呼叫 SDK；Provider adapter 實作 |
| **M2-B … M2-E** | interface / DTO / stub DI / audit schema | SDK **adapter**、真 LLM **呼叫** |
| **M3-A** | fake `LlmProvider`、並行 raw answer 編排 | 真 API、SDK adapter |
| **M3-B+** | `app/AI/Providers/*` bridge SDK → `LlmProvider` | domain 直接依賴 SDK facade |

---

## Milestone 2 — Laravel Skeleton ✅

| Gate | 目錄 | 狀態 | 說明 |
|------|------|------|------|
| M2-A … M2-E | [M2-A/](M2-A/) … [M2-E/](M2-E/) | **RELEASED** | 2026-06-13 完成 |

---

## Milestone 3 — Provider Integration（2 Gate）

> 2026-06-13 自原 4 Gate 精簡：fake+編排合併為 A；三 backend adapter 合併為 B。

| Gate | 目錄 | 狀態 | 說明 |
|------|------|------|------|
| **M3-A** | [M3-A/](M3-A/) | **OPEN** | fake provider + F01 + 並行 raw answer |
| M3-B | [M3-B/](M3-B/) | BLOCKED | SDK adapter（OpenAI / Claude / Gemini） |

---

## 派工入口

**現在派 M3-A**：將 [M3-A/brief.md](M3-A/brief.md) 與 [M3-A/progress.md](M3-A/progress.md) 一併交給 Worker。

交還 Orchestrator 時：

```markdown
## Gate: M3-A
## progress.md
（附連結或 diff：progress 已勾選 §1、§2 已貼輸出）
## 備註
```
