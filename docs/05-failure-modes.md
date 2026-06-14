# 05 — Failure Modes（失敗模式與狀態機）

本文件定義 Provider / Extractor 失敗行為與 partial success 狀態機。狀態枚舉見 [02-contracts.md](02-contracts.md)；Cases 對應見 [03-consensus-algorithm.md](03-consensus-algorithm.md)。

---

## 1. 設計原則

1. 所有失敗情境 **MUST** 有明確行為，不得交由實作者自由發揮。
2. `provider_status` 與 `extraction_status` **MUST** 分離描述。
3. 可分析 success **MUST** 定義為：`provider_status = success` AND `extraction_status = success`。
4. 單一 provider 失敗 **MUST NOT** 中斷整體 pipeline。

---

## 2. 失敗類型與狀態組合

| 情境 | provider_status | extraction_status | 納入 consensus |
|------|-----------------|-------------------|----------------|
| 正常成功 | `success` | `success` | 是 |
| Provider 逾時 | `failed_timeout` | `not_started` | 否 |
| 缺 API Key | `provider_unavailable` | `not_started` | 否 |
| Provider 錯誤 | `provider_error` | `not_started` | 否 |
| Extractor 無效 JSON | `success` | `invalid_json` | 否（repair 成功後為 success） |
| Extractor 邏輯失敗 | `success` | `extraction_failed` | 否 |

---

## 3. Provider Timeout（§16.1）

**狀態**：

```text
provider_status = failed_timeout
extraction_status = not_started
```

**處理**：

- **MUST** 保存錯誤資訊。
- **MAY** 重試至多一次。
- 計入可分析 success 時 **MUST** 視為不可分析。

---

## 4. Extractor Invalid JSON（§16.2）

**狀態**：

```text
provider_status = success
extraction_status = invalid_json
```

**處理**：

1. **MUST** 保存 raw answer。
2. **repair** = 僅 lenient 本地解析：擷取字串中的 JSON 區段，容錯解析器嘗試**一次**。
3. MVP **MUST NOT** re-prompt extractor 或 provider。
4. lenient 解析成功 → `extraction_status = success`，納入 consensus。
5. lenient 解析失敗 → 維持 `invalid_json`，排除於 consensus。

**Development 實作（Post-M8 本機）**：`JsonResponseExtractor` **MAY** 額外容錯（仍為本地、無 re-prompt）：

- 從 markdown ` ```json ` 區塊或內嵌 `{…}` 擷取 JSON
- `direct_answer`：`true`/`false`、中文「是/否」、discrete 題缺欄位時從 `summary` 推斷
- 巢狀 wrapper（如 `verifications[0].claim`）解包為 normalized 形狀
- `ConfiguredRawAnswerAgent` 使用 Laravel AI `HasStructuredOutput`；SDK 結構化為空時 **SHOULD** 以 raw `text` 供 extractor

---

## 5. Missing API Key（§16.3）

**狀態**：

```text
provider_status = provider_unavailable
extraction_status = not_started
```

**處理**：

- **MUST NOT** 呼叫該 provider。
- **MUST** 記錄設定錯誤。
- **MUST NOT** 影響其他 provider。

---

## 6. Provider Error（§16.4）

**狀態**：

```text
provider_status = provider_error
extraction_status = not_started
```

**處理**：

- **MUST** 保存錯誤與 provider metadata。
- **MUST NOT** 納入 consensus 計算。

---

## 7. Partial Success — 單一狀態機（§16.5）

### 7.1 可分析 success 計數

設 `N` = 可分析 success 的 provider 數。

| N | Consensus 行為 | Trust 上限（仍受 caps） |
|---|----------------|------------------------|
| 3/3 | 正常 Case 1/2/3 | 可達 High |
| 2/3 | Case 4 Two-Provider | Medium |
| 1/3 | Case 5 Insufficient | Unknown |
| 0/3 | Case 6 Failure | Unknown |

### 7.2 N == 1（Insufficient）

- `Consensus = Insufficient`
- **MAY** 呈現唯一答案為「Single Provider Answer — Unverified」
- `Trust Level = Unknown`（base Unknown + caps）
- 報告 **MUST** 標示「未經多模型驗證」

### 7.3 N == 0（Failure）

兩種子情境，結果相同：

1. 所有 raw provider 呼叫失敗。
2. 所有 provider 有 raw answer，但 extraction 全部失敗。

**處理**：

- `Consensus = Failure`
- `Trust = Unknown`
- **MUST NOT** 產生最終答案；**MUST** 產生錯誤報告（含 extraction failure 說明若適用）

### 7.4 Insufficient vs Failure 互斥決策表

| 可分析數 | Case | Consensus | MUST NOT |
|----------|------|-----------|----------|
| == 0 | 6 | Failure | 判為 Insufficient |
| == 1 | 5 | Insufficient | 判為 Failure |
| >= 2 | 1–4 | 依算法 | 使用 Insufficient/Failure label |

> **MUST NOT** 使用「可分析 < 2」這類重疊條件；Insufficient **僅** == 1，Failure **僅** == 0。

---

## 8. 與 Consensus Cases 的對應

```text
N = count(analyzable success)

if N == 0:
    → Case 6 Failure
elif N == 1:
    → Case 5 Insufficient
elif N == 2:
    → Case 4 Two-Provider
else:
    → Case 1 / 2 / 3（見 03-consensus-algorithm.md）
```

棄權（`direct_answer = unknown`）不影響 N 的計數（N 仍為可分析 provider 數），但 **MUST** 影響 direct_answer 軸的多數計票（見 03 §7）。

---

## 9. 錯誤報告最低要求

當 `Consensus = Failure` 或 `Insufficient` 時，報告 **MUST** 包含：

- 各 provider 的 `provider_status` / `extraction_status`
- 失敗原因摘要
- 是否可重試（僅 timeout 可重試一次）
- 對使用者的明確限制說明（Unverified / 無最終答案）

---

## Traceability

| 本文件章節 | description.md |
|------------|----------------|
| §2 狀態組合 | §11.2, T3-K |
| §3 Timeout | §16.1 |
| §4 Invalid JSON | §16.2, T3-H |
| §5 Missing Key | §16.3 |
| §6 Provider Error | §16.4 |
| §7 Partial Success | §16.5, T2-E, T3-M |
