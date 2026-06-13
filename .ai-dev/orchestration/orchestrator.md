# Orchestrator 工作流程（Lead Agent 手冊）

本文件定義 **Orchestrator（Lead Agent）** 與 Worker Agent、使用者之間的協作協定。  
Milestone 1（`docs/00..07`）已完成；自 **Milestone 2 起** 依本流程推進。

**相關文件**：

- 決策來源：[decisions/description.md](../decisions/description.md)
- 正式 spec：`docs/00..07`
- 里程碑驗收：[docs/07-milestones.md](../../docs/07-milestones.md)
- 實作計畫：[planning/plan.md](../planning/plan.md) §Phase 6+
- 派工文件：[briefs/](briefs/)

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
| **Orchestrator** | 階段切分、Brief、**唯一實作審核**、**`docs/` 與根 README 整合**、跨文件對齊、閘門放行 | 本 chat（Lead Agent） |
| **Worker Agent** | 依 brief 產出**應用程式**交付物；**更新 progress.md**（含建議文件更新，不直接改 docs/README） | 使用者派發的其他 agent |
| **User（你）** | 派 Worker、轉交產出（實作）或轉交 spec 審查意見（M1） | 使用者 |

**鐵則（實作）**：Worker 產出 **不得** 直接視為放行；**MUST** 經 Orchestrator 審核整合 → 閘門 checklist → 更新 `gate-status.md`。

### 1.1 文件編輯權（M2+）

| 路徑 | Worker | Orchestrator |
|------|--------|--------------|
| `docs/`（含 `docs/README.md`） | **MUST NOT** 修改 | **唯一**可改動者（spec 對齊、里程碑、架構回寫） |
| 根目錄 `README.md` | **MUST NOT** 修改 | **唯一**可改動者（Development、索引、對外說明） |
| `.ai-dev/orchestration/briefs/<Gate>/progress.md` | **MUST** 更新（§1–4） | 審核後填 §5 |
| `.ai-dev/` 其餘（`gate-status`、brief、handoff 等） | **MUST NOT** 修改 | 維護 |
| 應用程式碼（`app/`、`config/`、`routes/`、`database/` 等） | 依 Gate brief | 審核、必要時整合修正 |

**流程**：實作若需回寫 spec 或 README，Worker 在 progress **§4「建議 Orchestrator 文件更新」**列點（含建議段落／理由）；Orchestrator 於**放行前或放行時**統一撰寫，避免格式與用語不一致。

**Worker 仍 MUST 必讀** `docs/` 與根 README（唯讀）；不得為「順手更新文件」而改動上述路徑。

---

## 2. 實作階段生命週期（M2+）

```text
Orchestrator 發布 Gate Brief
        │
        ▼
User 派給 Worker Agent
        │
        ▼
Worker 實作 → 更新 progress.md → User 轉交 Orchestrator
        │
        ▼
Orchestrator：審核 + 整合修正 + 對齊 spec/已放行 Gate
        │              + 更新 docs/ / 根 README（若 progress 有建議或審核發現需回寫）
        │
        ├── 未通過 → 退回 Worker（Blocking 清單 + spec 引用）
        │
        └── 通過 → 填 progress §5 → 更新 gate-status.md → 發下一 Gate Brief
```

---

## 3. 交給 Orchestrator 的格式

### 3.1 實作 Gate（M2+，常用）

```markdown
## Gate: M2-A
## progress.md
（已更新：briefs/M2-A/progress.md — §1 勾選、§2 驗收輸出、§3 檔案清單）
## 備註（可選）
```

Orchestrator **MUST** 對照 `progress.md` 勾選項與 repo 實際狀態；通過後填 progress §5 並更新 gate-status。

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

### 4.4 文件（M2+ 必跑）

- [ ] Worker **未**修改 `docs/` 或根 `README.md`（若有 diff 即 Blocking）
- [ ] progress §4「建議 Orchestrator 文件更新」已處理或標記 N/A
- [ ] 需回寫的 spec / README 變更已由 **Orchestrator** 完成，用語與既有 docs 一致

---

## 5. Milestone 2–6 Gate 切分

同一 Milestone 內，編號小的未 **RELEASED**，大的 **不得** 開工。

### Milestone 2：Laravel Skeleton

| Gate | Worker 交付物 | 邊界（MUST NOT） |
|------|---------------|------------------|
| **M2-A** | Laravel 13 專案、`.env.example`、`.gitignore`；**（Lead 選項）** `laravel/ai` 套件+config+migrate | Consensus 業務；SDK **呼叫**/adapter；**docs/、根 README** |
| **M2-B** | `app/Consensus/` + 02 §9 interface/DTO 骨架 | 方法實作；SDK **呼叫** |
| **M2-C** | `config/consensus.php`、DI wiring（**stub only**） | SDK **adapter**、真 LLM **呼叫** |
| **M2-D** | audit migrations + models | consensus 算法 |
| **M2-E** | routes + 健康檢查；`php artisan` 可跑 | UI；**docs/、根 README**（Orchestrator 整合 serve/curl 說明） |

### Milestone 3：Provider Integration

> **2026-06-13 精簡**：M2 節奏偏快，除可並行項外改 **2 Gate**（原 4 Gate）。M4 放行 M3 後再視節奏調整切分。

| Gate | Worker 交付物 | 邊界（MUST NOT） |
|------|---------------|------------------|
| **M3-A** | fake `LlmProvider` + F01 replay；**並行** raw answer 編排；per-provider 持久化；timeout/重試 | 真 API；SDK adapter；extractor |
| **M3-B** | `app/AI/Providers/*` bridge SDK → `LlmProvider`；**OpenAI + Claude + Gemini** 三 backend | domain 直接依賴 SDK；consensus 算法 |

**M3 鐵則**：fake 優先（M3-A）；domain **MUST NOT** 依賴 SDK facade。`laravel/ai` 已於 M2-A 預裝；**adapter 與 API 呼叫**自 M3-B 起。

~~原 M3-A～D（4 Gate）~~ → 合併為上表 2 Gate。

### Milestone 4–6

**M4（2026-06-13 精簡為 3 Gate）**：

| Gate | Worker 交付物 | 邊界 |
|------|---------------|------|
| **M4-A** | Question Classifier + Response Extractor + CT-G1–G3 | Aligner / Analyzer / Trust / Verdict |
| **M4-B** | Claim Aligner + Consensus Analyzer + Trust Level Scorer | Verdict；F01–F14 全量 |
| **M4-C** | Verdict Reporter + F01–F14 整合驗收 | UI（M6） |

~~原 M4-A～F（6 Gate）~~ → 合併為上表 3 Gate。

M5-A/B、M6-A/B 不變。

---

## 6. Worker Brief 模板

```markdown
# Worker Brief — Gate [M2-A]

## 角色
關鍵報告 Worker。只做本 Gate。

## 必讀
1. [handoff.md](handoff.md) Top 10
2. `docs/[相關 spec]`
3. [gate-status.md](gate-status.md)
4. [briefs/](briefs/) 對應 Gate 的 **brief.md + progress.md**

## 交付物
- [ ] ...

## MUST NOT
- 修改 `docs/`、根目錄 `README.md`（Orchestrator 專責；需求寫入 progress §4）
- ...

## 完成後交還使用者轉 Orchestrator
1. 更新 progress.md（§1 勾選、§2 驗收輸出、§3 檔案清單、§4 建議文件更新）
2. 已知限制 / 留給下一 Gate
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

**M4-A** — 派工：[briefs/M4-A/](briefs/M4-A/) · 狀態：[gate-status.md](gate-status.md)

M3 已完成；M4 表：[briefs/README.md](briefs/README.md)
