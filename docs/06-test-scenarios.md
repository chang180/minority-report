# 06 — Test Scenarios（測試場景）

本文件定義 MVP regression fixtures（Fixture 1–14）與 Success Criteria 對照。算法見 [03-consensus-algorithm.md](03-consensus-algorithm.md)；Trust 見 [04-trust-level.md](04-trust-level.md)；失敗模式見 [05-failure-modes.md](05-failure-modes.md)。

---

## 1. 測試策略

1. Fixtures **MUST** 以 **fake provider** 注入，**MUST NOT** 依賴真模型 API。
2. fake provider **MUST** 為一等公民（見 [02-contracts.md](02-contracts.md) §8）。
3. 每個 fixture **MUST** 可獨立重播（deterministic consensus path）。
4. Majority / Minority 路徑 **MUST** 主要由 fake fixtures 驗證（真模型 MVP 幾乎不自然觸發）。

### 1.1 Fake Provider 注入（Interface 層）

```text
FakeProviderRegistry.register(fixtureId, behavior)
→ 回傳預設 raw_answer + extractor 可解析的 normalized JSON
→ 可覆寫 provider_status / extraction_status 模擬失敗
```

實作細節屬 Milestone 3；測試 **MUST** 只依賴 `LlmProvider` + `ResponseExtractor` 契約。

---

## 2. Fixture 模板

每個 fixture 包含：

| 欄位 | 說明 |
|------|------|
| ID | F01–F14 |
| 分類 | Type / answer_shape |
| 輸入 | 三 provider（或失敗模擬）的 normalized 關鍵欄位 |
| 可分析數 N | 預期 |
| 期望 Consensus | 含 Minority Provider |
| 期望 Trust | final_trust |
| Minority Report | 是 / 否 |
| 備註 | 邊界說明 |

---

## 3. Fixtures 1–14

### F01 — Full Consensus（discrete）

| 項目 | 值 |
|------|-----|
| 分類 | Type B, discrete |
| 輸入 | P1/P2/P3: `direct_answer = yes`；無 major claim 衝突 |
| N | 3 |
| Consensus | `Full` |
| Trust | `High` |
| Minority Report | 否 |

---

### F02 — Majority vs Minority（discrete）

| 項目 | 值 |
|------|-----|
| 分類 | Type B, discrete |
| 輸入 | P1=yes, P2=yes, P3=no |
| N | 3 |
| Consensus | `Majority`, Minority Provider = P3 |
| Trust | `Medium` |
| Minority Report | **是** |

---

### F03 — No Consensus（discrete + 棄權）

| 項目 | 值 |
|------|-----|
| 分類 | Type B, discrete |
| 輸入 | P1=yes, P2=no, P3=unknown |
| N | 3（P3 仍可分析；棄權不計票） |
| 有效票 | yes / no → 1 vs 1 |
| Consensus | `None` |
| Trust | `Low` |
| Minority Report | 否 |

---

### F04 — Type C Without Grounding

| 項目 | 值 |
|------|-----|
| 分類 | Type C, discrete（例：最新版本問題） |
| 輸入 | 三 provider 一致；`requires_grounding=true`, `grounding_available=false` |
| N | 3 |
| Consensus | `Full` |
| Trust | `Low`（C cap）；**MUST NOT** High |
| Minority Report | 否 |

---

### F05 — Provider Timeout（2/3）

| 項目 | 值 |
|------|-----|
| 分類 | Type B, discrete |
| 輸入 | P1 timeout (`failed_timeout`)；P2/P3 一致 yes |
| N | 2 |
| Consensus | `Full (2-only)` |
| Trust | `Medium` |
| Minority Report | 否 |
| `final_verdict` | **MUST** 含「參與 provider」「缺席 provider」；缺席席標示 `failed_timeout`（繁中：呼叫逾時） |

---

### F06 — Extractor Invalid JSON

| 項目 | 值 |
|------|-----|
| 分類 | Type B, discrete |
| 輸入 | P1 extractor 回傳無法解析 JSON；lenient repair 失敗；P2/P3 一致 |
| 中間狀態 | P1: `provider_status=success`, `extraction_status=invalid_json` |
| N | 2 |
| Consensus | `Full (2-only)` |
| Trust | `Medium` |
| 約束 | **MUST NOT** re-prompt |
| `final_verdict` | **MUST** 含缺席席與 `JSON 解析失敗` 標籤 |

---

### F07 — direct_answer 一致但 claim 衝突

| 項目 | 值 |
|------|-----|
| 分類 | Type B, discrete |
| 輸入 | P1/P2/P3 `direct_answer=yes`；P3 的 aligned `date` claim 與 P1/P2 不同 |
| N | 3 |
| Consensus | `Majority`, Minority Provider = P3 |
| Trust | `Low`（major claim conflict cap） |
| Minority Report | **是** |

---

### F08 — open 題、canonical_key 對齊

| 項目 | 值 |
|------|-----|
| 分類 | Type B, **open**（例：Laravel migration 用途） |
| 輸入 | 三方 claims 的 `canonical_key` 正規化後可配對；無 major 衝突 |
| N | 3 |
| Consensus | `Full`（走 open 主鍵；**忽略** direct_answer） |
| Trust | `High` |
| Minority Report | 否 |
| **備註** | 測 **extractor** `canonical_key` 一致性（三方 key 已可字串配對）；**非** aligner 語意能力；語意對齊見 **F16** |

---

### F09 — open 題、low-discriminability

| 項目 | 值 |
|------|-----|
| 分類 | Type B, open |
| 輸入 | 無 `{boolean,date,number,version}` 型 claim |
| N | 3 |
| Consensus | `Full (low-discriminability)` |
| Trust | `Medium` |
| 報告 | **MUST** 標示只能確認無可機械比對的重大衝突 |
| Minority Report | 否 |

---

### F10 — 1/3 success

| 項目 | 值 |
|------|-----|
| 分類 | Type B, discrete |
| 輸入 | 僅 P1 可分析成功；P2/P3 不可分析 |
| N | 1 |
| Consensus | `Insufficient` |
| Trust | `Unknown` |
| 呈現 | **MAY** Single Provider Answer — Unverified |
| Minority Report | 否 |

---

### F11 — 全部 extraction 失敗

| 項目 | 值 |
|------|-----|
| 分類 | Type B, discrete |
| 輸入 | 三 provider raw success；三 extraction 全失敗 |
| N | 0 |
| Consensus | `Failure` |
| Trust | `Unknown` |
| 最終答案 | **MUST NOT** 產生；extraction failure report |
| Minority Report | 否 |

---

### F12 — Two-provider conflict

| 項目 | 值 |
|------|-----|
| 分類 | Type B, discrete 或 open |
| 輸入 | N=2；兩者有 major claim 衝突 |
| Consensus | `None` |
| Trust | `Low`（可分析==2 → Medium；major conflict → Low → final **Low**） |
| Minority Report | **MUST NOT**（2/3 無 Majority） |

---

### F13 — 棄權不觸發 Minority Report

| 項目 | 值 |
|------|-----|
| 分類 | Type B, discrete |
| 輸入 | P1=yes, P2=yes, P3=unknown；無 major claim 衝突 |
| N | 3 |
| 有效票 | yes / yes |
| Consensus | `Full` |
| Trust | `Medium`（**有效 direct_answer 表態數 == 2** cap；可分析數仍為 3） |
| Minority Report | **MUST NOT**；P3 **MUST NOT** 列為 Minority Provider |

對照 F03：`yes/no/unknown` → None。

---

### F14 — 多軸衝突指向不同 provider

| 項目 | 值 |
|------|-----|
| 分類 | Type B, discrete |
| 輸入 | direct_answer: yes/yes/no（P3 少數）；aligned date claim 少數方 **P2** |
| N | 3 |
| Consensus | `None`（§12.6 收斂失敗；**MUST NOT** Majority） |
| Trust | `Low` |
| 報告 | **MUST** 並陳兩處衝突 |
| Minority Report | 否 |

---

### F16 — Semantic key alignment（M8-C）

| 項目 | 值 |
|------|-----|
| 名稱 | F16 — Semantic Key Alignment（demo catalog：`M8-F16`） |
| 分類 | Type B, **open** |
| 輸入 | 三方 analyzable；`date` claim **value 相同**（如 `2024-03-12`）；`canonical_key` 分別為 `release date` / `product launch date` / `official launch date` |
| `string` mode | keys 字串不配對 → 多 unmatched |
| `semantic_llm` mode（mock） | keys 合併 aligned → **無 major 衝突** |
| N | 3 |
| Consensus（semantic） | `Full`（open 主鍵） |
| Trust（semantic） | `High` |
| Minority Report | 否 |
| **備註** | 與 F08 互補：F08 測 **一致 key**；F16 測 **aligner 語意** 合併 |

---

## 4. Classifier 單元測試（T2-G fail-safe bias）

Fail-safe bias（[02-contracts.md §2.4](02-contracts.md)）是 Trust-cap 體系的地基，但**不適合**以 consensus fixture 覆蓋（F04 只驗 Type C 處理，不驗低信心降級）。  
**MUST** 以 deterministic 單元測試覆蓋（**MUST NOT** 依賴真 LLM）。

| ID | 輸入（LLM 原始輸出 + 後處理前） | 期望（後處理後） |
|----|--------------------------------|------------------|
| **CT-G1** | `classifier_confidence=low`，`type=B`，`requires_grounding=false`（邊界：時效/版本類模糊 factual） | **MUST** 升級為 `type=C`，`requires_grounding=true` |
| **CT-G2** | `classifier_confidence=low`，`type=A` | **MUST** 升級為 `type=B`（仍進 multi-LLM 路徑，不走 A 單模型快速路徑） |
| **CT-G3** | `classifier_confidence=high`，任意合法 `type` | **MUST NOT** 改變 LLM 原始 `type` / `requires_grounding` |

實作位置：Milestone 4 Classifier（M4-A）。測試路徑建議：

```text
tests/Unit/Consensus/Classifier/FailSafeBiasTest.php
```

---

## 5. Success Criteria 對照

| # | 準則 | 驗證方式 |
|---|------|----------|
| 1 | Type A/B/C 分類 + answer_shape + fail-safe | **CT-G1–G3** 單元測試 + F04 |
| 2 | fake provider 完整 workflow | F01–F14 整合測試 |
| 3 | 3/3、2/3、1/3、0/3 | F01, F05, F10, F11 |
| 4 | provider vs extractor 失敗分離 | F05, F06, F11 |
| 5 | Full Consensus discrete/open | F01, F08 |
| 6 | Full (low-discriminability) | F09 |
| 7 | Majority + claim 衝突掩蓋 | F07 |
| 8 | unknown 棄權 | F13, F03 |
| 9 | 多軸 → None | F14 |
| 10 | Minority Report 產出 | F02, F07 |
| 11 | No Consensus | F03, F12, F14 |
| 12 | base + caps 瀑布 | F01–F14 vs [04](04-trust-level.md) §4 |
| 13 | audit trail 完整 | Milestone 5 驗收 |
| 14 | MVP 字串對齊 vs M8-C 語意 | F08、F16 |
| 15 | Fixture 1–14 regression | 本文件 F01–F14 |
| 16 | M8-C semantic alignment | F16 + `M8CSemanticAlignmentTest` |

---

## 6. 測試 ID 命名

```text
tests/Fixtures/Consensus/F{01..14}Test.php   ← PHPUnit 整合（Milestone 4+）
tests/Fixtures/Consensus/F16Test.php         ← M8-C semantic（opt-in / mock）
tests/Unit/Consensus/Classifier/FailSafeBiasTest.php  ← CT-G1–G3
fixture_id: F01 .. F14
classifier_test_id: CT-G1 .. CT-G3
```

---

## Traceability

| 本文件章節 | description.md |
|------------|----------------|
| §1 策略 | §19, §3.2 |
| §3 Fixtures | §19 Fixture 1–14 |
| §4 Classifier CT-G | §9 fail-safe, T2-G |
| §5 Success Criteria | §22 |
| F08 備註 | T3-P |
| F13/F14 | T3-O, T3-N |
