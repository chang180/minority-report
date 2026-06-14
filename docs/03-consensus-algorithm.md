# 03 — Consensus Algorithm（共識演算法）

本文件定義 Hybrid Analyzer 的確定性比對、claim 對齊、共識 Cases 與 Minority Report 輸出。資料契約見 [02-contracts.md](02-contracts.md)。

---

## 1. 設計原則

1. **MUST NOT** 使用純 LLM Judge 作為唯一最終裁決者。
2. MVP 採 **Hybrid Analyzer**：deterministic comparison + rule-based classification + LLM-assisted report generation。
3. LLM **MAY** 用於：整理 claims、產生自然語言報告、解釋差異。
4. LLM **MUST NOT** 作為唯一裁決者。

### 1.1 流程

```text
Provider raw answers
        │
        ▼
Per-provider independent extraction
        │
        ▼
Cross-provider claim alignment
        │
        ▼
Deterministic comparison + rule-based classification
        │
        ▼
LLM-assisted report generation (non-binding)
```

---

## 2. 判定輸入

Consensus Analyzer **MUST** 接收：

| 輸入 | 來源 |
|------|------|
| 可分析 provider 數 | `provider_status = success` AND `extraction_status = success` 的數量 |
| `answer_shape` | Classifier / normalized |
| discrete 題的 `direct_answer` | 各 provider normalized（含棄權處理） |
| typed aligned claims 的重大衝突結果 | Aligner + Analyzer |
| low-discriminability 判定 | Analyzer（open 題專用） |

---

## 3. Discrete vs Open 主鍵

| answer_shape | 一致性主鍵 |
|--------------|------------|
| `discrete` | `direct_answer`（仍 **MUST** 檢查重大 claim 衝突） |
| `open` | **MUST** 忽略 `direct_answer`；一致性看是否存在重大 claim 衝突 |

### 3.1 Low-discriminability

open 題若抽不出任何 `{boolean, date, number, version}` 型 claim，稱為 **low-discriminability**。

- 系統 **MAY** 表示「沒有偵測到可機械比對的重大衝突」。
- **MUST NOT** 將此視為高可信共識；Trust 受 cap（見 [04-trust-level.md](04-trust-level.md)）。

---

## 4. Claim Alignment

MVP 對齊規則 **MUST** 保守、可測試：

1. 先依 claim `type` 分組。
2. 同組內，以 `canonical_key` 做正規化字串比對。
3. 正規化：小寫、去標點、去多餘空白、去停用詞後相等。
4. 出現在 ≥2 provider 的配對 claim → **aligned claim**，進入 §5 比對。
5. 只出現在單一 provider → **unmatched claim**；不計入衝突判定，**MUST** 在報告 surface。

**已知限制**：字串比對無法處理語意相同但措辭差距大的 claim。

> **M8-C（Post-MVP）**：可選 **語意 key 聚類**（Admin `semantic_llm` mode）；見 [11-semantic-alignment.md §5](11-semantic-alignment.md)。**value 衝突判準仍為本節 §5 確定性規則**；LLM **MUST NOT** 作 value 裁決。

> Fixture 8 測試的是 extractor 的 `canonical_key` 一致性，**不是** aligner 語意能力。語意對齊見 Fixture F16（[06-test-scenarios.md](06-test-scenarios.md)）。

---

## 5. 逐型別衝突判準

對 aligned claim，依 `type` 套用確定性規則：

| type | 衝突判準 |
|------|----------|
| `boolean` | value 不 exact-match → 衝突 |
| `date` | 正規化 ISO 後，於雙方共有最粗粒度比較；不相等 → 衝突 |
| `number` | 同 `unit` 下相對誤差 > 5% → 衝突（門檻 **MUST** 可配置） |
| `version` | 正規化 semver 後不 exact-match → 衝突 |
| `entity` | MVP 不自動判衝突，只 surface |
| `source` | MVP 不自動判衝突，只 surface |
| `statement` | MVP 不自動判衝突，只 surface |

- `unit` 不同且無法換算 → 標記 **unalignable**；不計衝突，**MUST** surface。

**重大 claim 衝突**：在 `{boolean, date, number, version}` 四型 aligned claims 中，存在至少一個衝突。

---

## 6. Majority Claim 判定

對同一 aligned typed claim，將各 provider 的 normalized value 分組：

| 分組結果 | 判定 |
|----------|------|
| 2 vs 1（一 provider 與其他兩個不同） | 該 provider 為該 claim 的 **minority owner** |
| 三個 value 互不相同 | **no-majority conflict** |
| 僅兩 provider 可比且彼此不同 | **1 vs 1 conflict**；不產生 Minority Provider |

此規則同時適用 discrete 與 open 題。

---

## 7. 棄權（Abstention）處理

`direct_answer = unknown` **MUST** 視為棄權，**不是**反對。

計算多數時：

1. **MUST** 先排除所有 `direct_answer = unknown` 的 provider。
2. 以「有效表態數」計票。

| 有效表態數 | 行為 |
|------------|------|
| 3 | 照常判定 |
| 2，且一致 | `Consensus = Full`；Trust 套用 [04 §3.3](04-trust-level.md)（有效 direct_answer 表態數 == 2 → Medium） |
| 2，且不一致 | `Consensus = None` |
| < 2 | 比照 [05-failure-modes.md](05-failure-modes.md) Insufficient |

**MUST NOT** 將棄權者列為 Minority Provider；**MUST NOT** 因棄權單獨觸發 Minority Report。

範例：

- `yes / yes / unknown` → 排除後 Full；Trust 套用 04 §3.3（有效表態==2 → Medium）；**無** Minority Report。
- `yes / no / unknown` → 排除後 1 vs 1 → `Consensus = None`。

---

## 8. 多軸衝突收斂

一題可能同時在 direct_answer 軸與多個 typed claim 軸出現分歧。判定 Majority **MUST** 收斂到**單一** minority owner：

1. **僅當**所有重大衝突（含 direct_answer 分歧與所有重大 claim 衝突）都可歸因於**同一** minority provider → `Consensus = Majority`。
2. 不同衝突指向不同 provider → `Consensus = None`。
3. 任一重大衝突為 no-majority（三者皆異）→ `Consensus = None`。

此優先序 **MUST** 高於 Case 2 個別觸發條件（避免 Case 2 與 Case 3 互撞）。

範例（Fixture 14）：direct_answer 少數方 P3，但 date claim 少數方 P2 → `Consensus = None`。

---

## 9. Consensus Cases

先依可分析 provider 數分支，再依一致性与衝突判定。

```text
可分析數 N
├── N == 0 → Case 6 Failure
├── N == 1 → Case 5 Insufficient
├── N == 2 → Case 4 Two-Provider
└── N >= 3 → Case 1 / 2 / 3（含 low-discriminability 變體）
```

### Case 1: Full Consensus

**條件**：≥3 可分析，且：

- discrete：`direct_answer` 全部相等（棄權已排除後若有效票 <3 走 §7），且無重大 claim 衝突。
- open：無重大 claim 衝突。

**結果**：`Consensus = Full`

若 open 且 low-discriminability：`Consensus = Full (low-discriminability)`；Trust cap Medium。

### Case 2: Majority vs Minority

**條件**：≥3 可分析，且出現明確 2 vs 1：

- discrete：一 provider 的 `direct_answer` 與其他兩個不同（棄權已排除）。
- claim：某重大衝突 claim 出現 2 vs 1，可識別 minority owner。

**結果**：

```text
Consensus = Majority
Minority Provider = provider_name
```

**MUST** 產生 Minority Report。

- direct_answer 一致但重大 claim 2 vs 1 → 仍判 Majority。
- **MUST** 先通過 §8 收斂檢查；多軸少數方不一致 → 改判 None。

### Case 3: No Consensus

**條件**：≥3 可分析，且：

- discrete：有效表態三者互不相同，或 1 vs 1 後無多數。
- claim：no-majority conflict，或多處重大衝突無法歸納單一 minority owner。

**結果**：`Consensus = None`

### Case 4: Two-Provider（2/3 可分析）

**條件**：恰 2 可分析。

| 情況 | 結果 |
|------|------|
| 一致 | `Consensus = Full (2-only)`；Trust 套用 [04 §3.2](04-trust-level.md)（可分析 provider 數 == 2 → Medium） |
| 不一致 | `Consensus = None`；Trust ≤ Medium |

2/3 **MUST NOT** 產生 Majority / Minority Report。

### Case 5: Insufficient

**條件**：可分析 provider **== 1**

**結果**：`Consensus = Insufficient`；不得高信任裁決；**MAY** 呈現 Single Provider Answer — Unverified。

### Case 6: Failure

**條件**：可分析 provider **== 0**（全部 provider 失敗，或全部 extraction 失敗）

**結果**：`Consensus = Failure`；`Trust = Unknown`；不產生答案，只產生錯誤報告。

> Case 5 與 Case 6 **MUST** 嚴格互斥：== 1 為 Insufficient，== 0 為 Failure。

---

## 10. Minority Report 輸出

當 `Consensus = Majority` 時，輸出 **MUST** 包含（使用者可見標題為**繁體中文**；audit `report_type` / `consensus.status` 仍英文）：

```text
多數意見
少數意見
爭議主張
證據比對
最終判定
信任等級
已知限制
```

**MUST NOT** 忽略少數意見。

### 10.1 Partial participation（M8-D）

當 `analyzableCount < providerCount`（例如 F05 逾時、F06 抽取失敗）且仍產出 `final_verdict` 時，輸出 **MUST** 額外包含：

```text
參與 provider：{names}（N/M）
缺席 provider：{name} — {原因標籤}：{error.message 若有}
```

- **MUST NOT** 將技術缺席列為 Minority（見 Case 4：`2/3 MUST NOT` Minority Report）。
- 缺席原因標籤對照見 [05-failure-modes.md §10](05-failure-modes.md)。

### 10.2 Evidence Comparison（MVP 範圍）

MVP 無外部 grounding，**MUST NOT** 裁定哪方證據較強。

MVP **ONLY**：

- 並陳各方說法。
- 並陳各方自報 citations。
- 標示來源尚未經外部驗證。

來源品質裁定延後 Phase 3。

---

## Traceability

| 本文件章節 | description.md |
|------------|----------------|
| §1 Hybrid Analyzer | §12.1 |
| §4 Claim Alignment | §12.2, T3-P |
| §5 衝突判準 | §12.3 |
| §6 Majority Claim | §12.4 |
| §7 棄權 | §12.5, T3-O |
| §8 多軸收斂 | §12.6, T3-N |
| §9 Cases | §13, T3-M |
| §10 Minority Report | §14, T3-I |
