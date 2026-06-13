# 04 — Trust Level（信任等級）

本文件定義 MVP 信任等級的 base + caps 瀑布算法與可測試 decision table。共識結果見 [03-consensus-algorithm.md](03-consensus-algorithm.md)。

---

## 1. 設計原則

1. MVP **MUST NOT** 輸出精確百分比 Trust Score（例如 `92%`）。
2. MVP **MUST** 使用分級：`High` \| `Medium` \| `Low` \| `Unknown`。
3. **MUST** 使用 base + caps 瀑布，**MUST NOT** 使用重疊 OR 清單。
4. Type C 且無 grounding 時 **MUST NOT** 輸出 High Trust。
5. Phase 3 前 **MUST NOT** 引用尚未實作的 grounding 提高 trust。

### 1.1 排序

```text
Unknown < Low < Medium < High
```

實作 **MUST** 將等級映射為有序整數以便取 min。

### 1.2 兩個獨立計數（Scorer 輸入）

Trust Scorer **MUST** 在 context 中區分：

| 計數 | 定義 | 典型情境 |
|------|------|----------|
| **可分析 provider 數** | `provider_status = success` 且 `extraction_status = success` 的 provider 數 | Case 4（2/3）、F05 |
| **有效 direct_answer 表態數** | discrete 題；排除 `direct_answer = unknown` 棄權後的表態 provider 數 | F13（3 可分析、2 票有效） |

兩者 **MUST NOT** 混為同一欄位。open 題不適用有效表態數（無 direct_answer 投票）。

---

## 2. 算法

### 步驟一：計算 base

由 consensus 結果決定：

| Consensus | base |
|-----------|------|
| `Full` | High |
| `Full (2-only)` | High |
| `Full (low-discriminability)` | High |
| `Majority` | Medium |
| `None` | Low |
| `Insufficient` | Unknown |
| `Failure` | Unknown |

### 步驟二：收集 applicable caps

| 條件 | cap |
|------|-----|
| Type C 且 `grounding_available = false` | Low |
| 可分析 provider 數 == 2 | Medium |
| 有效 direct_answer 表態數 == 2（discrete；排除 `unknown` 棄權後） | Medium |
| 存在任何重大 claim 衝突 | Low |
| open 題且 low-discriminability | Medium |
| `Consensus = None` | Low |
| `Consensus = Insufficient` 或 `Failure` | Unknown |

多條件同時成立時 **MUST** 全部套用。

**Medium cap 觸發**：§1.2 的兩個計數為**獨立條件**；**任一** == 2 即套用 Medium cap（**MUST NOT** 把有效表態數塞進「可分析數 == 2」的定義）。

### 步驟三：瀑布

```text
final_trust = min(base, all_applicable_caps)
```

`min` 取最嚴格（最低）等級。

---

## 3. Cap 觸發細節

### 3.1 Type C 無 grounding

- 當 `classification.type = C` 且 `grounding_available = false`（MVP 恆真）→ cap Low。
- 即使 `Consensus = Full` 且 3/3 可分析，final **MUST NOT** 高於 Low。

### 3.2 可分析 provider 數 == 2

- 觸發條件：**僅**當可分析 provider 數 == 2（Case 4 Two-Provider、F05 等）。
- cap → Medium。
- **MUST NOT** 因 F13（可分析數 == 3）觸發本條。

### 3.3 有效 direct_answer 表態數 == 2

- 觸發條件：**僅** discrete 題；排除 `direct_answer = unknown` 後，有效表態數 == 2。
- 可分析 provider 數 **MAY** 仍為 3（F13：`yes/yes/unknown`）。
- cap → Medium。
- **MUST NOT** 與 §3.2 共用同一觸發欄位或 cap 名稱。

### 3.4 重大 claim 衝突

- 只要 analyzer 判定存在重大 claim 衝突 → cap Low。
- 與 `Consensus = None` cap 可同時存在；結果仍為 Low。

### 3.5 open + low-discriminability

- `Consensus = Full (low-discriminability)` 時 base 為 High，此 cap → Medium。

### 3.6 Consensus = None / Insufficient / Failure

- None → cap Low（base 已是 Low，通常無額外效果）。
- Insufficient / Failure → cap Unknown（base 已是 Unknown）。

---

## 4. Decision Table（可測試矩陣）

以下假設 Type B、`grounding_available = false`、無 low-discriminability、無 major claim 衝突，除非表中另有說明。

### 4.1 依 Consensus（基線）

| Consensus | 可分析數 | 有效表態數 | 觸發 caps | final_trust |
|-----------|----------|------------|-----------|-------------|
| Full | 3 | 3 | （無） | **High** |
| Full | 3 | 2 | 有效表態==2 → Medium | **Medium**（F13） |
| Full (2-only) | 2 | 2 | 可分析==2 → Medium | **Medium**（F05） |
| Full (low-discriminability) | 3 | — | open+low-disc → Medium | **Medium**（F09） |
| Majority | 3 | 3 | （無 claim cap 時） | **Medium**（F02） |
| Majority + major claim 衝突 | 3 | 3 | major conflict → Low | **Low**（F07） |
| None | 3 | — | None → Low | **Low**（F03） |
| Insufficient | 1 | — | Insufficient → Unknown | **Unknown**（F10） |
| Failure | 0 | — | Failure → Unknown | **Unknown**（F11） |

### 4.2 Type C 覆寫

| Consensus | 可分析數 | final_trust |
|-----------|----------|-------------|
| Full | 3 | **Low**（C cap） |
| Full (2-only) | 2 | **Low**（min(Medium, Low)） |
| Majority | 3 | **Low** |
| None | 3 | **Low** |

### 4.3 複合 caps 範例

| 情境 | caps 套用 | final_trust |
|------|-----------|-------------|
| F12：2 可分析 + major conflict | 可分析==2 → Medium；major conflict → Low | **Low** |
| F13：3 可分析，`yes/yes/unknown` | 有效表態==2 → Medium（**不**觸發可分析==2） | **Medium** |
| F14：3 可分析 + major conflict | major conflict → Low | **Low** |

### 4.4 Fixture 對照速查

| Fixture | Consensus | 主要 caps（canonical 名稱） | final_trust |
|---------|-----------|------------------------------|-------------|
| F1 | Full | — | High |
| F2 | Majority | — | Medium |
| F3 | None | None | Low |
| F4 | Full（Type C） | C cap | Low |
| F5 | Full (2-only) | 可分析==2 | Medium |
| F7 | Majority | major conflict | Low |
| F9 | Full (low-disc) | low-discriminability | Medium |
| F10 | Insufficient | Insufficient | Unknown |
| F11 | Failure | Failure | Unknown |
| F12 | None | 可分析==2 + major conflict | Low |
| F13 | Full | **有效表態==2** | Medium |
| F14 | None | major conflict | Low |

---

## 5. 輸出契約

TrustLevelResult **MUST** 包含：

```json
{
  "base_trust": "High",
  "analyzable_provider_count": 3,
  "effective_direct_answer_vote_count": 2,
  "applied_caps": [
    {"condition": "effective_direct_answer_vote_count_eq_2", "cap": "Medium"}
  ],
  "final_trust": "Medium"
}
```

- **MUST NOT** 輸出百分比。
- **MUST** 保存 base、兩個計數與 caps 供 audit trail。
- `applied_caps[].condition` **MUST** 使用與 §2 cap 表一致的 canonical 名稱（例如 `analyzable_provider_count_eq_2` vs `effective_direct_answer_vote_count_eq_2`）。

---

## 6. 測試要求

- **MUST** 以 decision table 單元測試覆蓋 §4 所有列（含 F13 的**有效表態==2**列，**不得**僅依可分析==2 推導）。
- **MUST** 與 [06-test-scenarios.md](06-test-scenarios.md) Fixture F01–F14 期望一致。
- 新增 cap 條件 **MUST** 同步更新 §2 cap 表、decision table 與 [description.md §15](../.ai-dev/decisions/description.md)。

---

## Traceability

| 本文件章節 | description.md |
|------------|----------------|
| §1.2 兩個計數 | §12.5, §15 |
| §2 cap 表 | §15, T2-D |
| §3.2 / §3.3 | §15, §12.5, Fixture 13 |
| §4 Decision table | §15, §19 |
| §5 輸出契約 | §18 |
