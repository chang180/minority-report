# Orchestrator 工作流程（Lead Agent 手冊）

本文件定義 **Orchestrator（Lead Agent）** 與 Worker Agent、使用者之間的協作協定。  
Milestone 1（`docs/00..07`）已完成；自 **Milestone 2 起** 依本流程推進。

**相關文件**：

- 決策來源：[description.md](description.md)
- 正式 spec：`docs/00..07`
- 里程碑驗收：[docs/07-milestones.md](../docs/07-milestones.md)
- 實作計畫：[plan.md](plan.md) §Phase 6+

---

## 0. 兩種審核模式

| 階段 | 審核方式 | 使用者角色 |
|------|----------|------------|
| **Spec（M1）** | 已完成（Claude 審查 → Orchestrator 整合，2026-06-13 patch） | — |
| **實作（M2+）** | **僅 Orchestrator 審核**；使用者只派 Worker、轉交產出 | 派工 + 轉交產出 |

> **實作階段不需要 External Review。** Orchestrator 負責 spec 對齊、Gate 邊界、測試與整合放行。

---

## 1. 角色分工

| 角色 | 責任 | 誰來做 |
|------|------|--------|
| **Orchestrator** | 階段切分、Brief、**唯一實作審核**、整合修正、跨文件對齊、閘門放行 | 本 chat（Lead Agent） |
| **Worker Agent** | 依 brief 產出指定交付物 | 使用者派發的其他 agent |
| **User（你）** | 派 Worker、轉交產出（實作）或轉交 spec 審查意見（M1） | 使用者 |

**鐵則（實作）**：Worker 產出 **不得** 直接視為放行；**MUST** 經 Orchestrator 審核整合 → 閘門 checklist → 更新 `gate-status.md`。

---

## 2. 實作階段生命週期（M2+）

```text
Orchestrator 發布 Gate Brief
        │
        ▼
User 派給 Worker Agent
        │
        ▼
Worker 產出 → User 轉交 Orchestrator
        │
        ▼
Orchestrator：審核 + 整合修正 + 對齊 spec/已放行 Gate
        │
        ├── 未通過 → 退回 Worker（Blocking 清單 + spec 引用）
        │
        └── 通過 → 更新 gate-status.md → 發下一 Gate Brief
```

---

## 3. 交給 Orchestrator 的格式

### 3.1 實作 Gate（M2+，常用）

```markdown
## Gate: M2-A
## Worker 產出
- 變更檔案 / diff / 摘要
- Worker 宣稱的驗收方式

## 你的備註（可選）
```

Orchestrator **MUST** 自行審核並回覆：Blocking / Non-blocking、是否放行、下一 Brief。

### 3.2 Spec 審查整合（M1，一次性）

```markdown
## Spec Review（Claude / 其他）
## 審查意見
1. ...

## 備註
- Laravel AI SDK：以 interface 化為準，非選型 PoC（已採納）
```

---

## 4. Orchestrator 審核檢查清單（每 Gate 必跑）

### 4.1 對 spec / Top 10

- [ ] 未弱化 handoff.md Top 10
- [ ] 術語與 [02-contracts.md](../docs/02-contracts.md) 一致
- [ ] consensus / trust / failure 與 03 / 04 / 05 / 06 無矛盾
- [ ] Laravel **13**、PHP 8.4+
- [ ] AI 呼叫經 domain interface；SDK 只在 `app/AI/`（01 §4）

### 4.2 對已完成 Gate

- [ ] 不與已放行程式碼 / schema 衝突
- [ ] migration 覆蓋 02 §10 Audit Trail（若適用）
- [ ] 未超出本 Gate 邊界（未偷做後續 Milestone）

### 4.3 測試（若適用）

- [ ] 宣稱的驗收命令可重現
- [ ] 對應 Fixture 子集通過（M4+）

---

## 5. Milestone 2–6 Gate 切分

同一 Milestone 內，編號小的未 **RELEASED**，大的 **不得** 開工。

### Milestone 2：Laravel Skeleton

| Gate | Worker 交付物 | 邊界（MUST NOT） |
|------|---------------|------------------|
| **M2-A** | Laravel 13 專案、`.env.example`、README | Consensus 業務邏輯 |
| **M2-B** | `app/Consensus/` + 02 §9 interface/DTO 骨架 | 方法實作 |
| **M2-C** | `config/consensus.php`、DI wiring | 真 LLM 呼叫 |
| **M2-D** | audit migrations + models | consensus 算法 |
| **M2-E** | routes + 健康檢查；`php artisan` 可跑 | UI |

### Milestone 3：Provider Integration

| Gate | Worker 交付物 | 備註 |
|------|---------------|------|
| **M3-A** | fake provider + F01 replay | **優先**；不接真 API |
| **M3-B** | 並行 raw answer 編排 | 不做 extractor |
| **M3-C** | Laravel AI SDK adapter + OpenAI backend | bridge 至 `LlmProvider` |
| **M3-D** | Claude + Gemini backend adapter | 可分批 |
| ~~M3-E~~ | （已移除）SDK 選型 PoC | 改為 M3-C/D 完成即足 |

**M3 鐵則**：fake 優先；domain **MUST NOT** 依賴 SDK facade。

### Milestone 4–6

見前版（M4-A…F、M5-A/B、M6-A/B），不變。

---

## 6. Worker Brief 模板

```markdown
# Worker Brief — Gate [M2-A]

## 角色
關鍵報告 Worker。只做本 Gate。

## 必讀
1. .ai-dev/handoff.md Top 10
2. docs/[相關 spec]
3. .ai-dev/gate-status.md

## 交付物
- [ ] ...

## MUST NOT
- ...

## 完成後交還使用者轉 Orchestrator
1. 變更檔案清單
2. 驗收命令
3. 已知限制
```

---

## 7. 狀態追蹤

見 **[gate-status.md](gate-status.md)**。Orchestrator 放行 **MUST** 更新。

---

## 8. 退回 Worker

1. **Blocking**（必改）
2. **Non-blocking**（可下一 Gate）
3. **Spec / Top 10 引用**
4. Worker 修正後 **直接** 再交 Orchestrator（不需 External Review）

---

## 9. 當前 Gate

**M2-A** — 待使用者派 Worker。說「發 M2-A brief」取得派工文本。

**並行**：M1 spec patch 已完成（Claude 審查整合）。可派 **M2-A** Worker。
