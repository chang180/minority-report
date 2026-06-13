# 關鍵報告（Minority Report）— Handoff

> 本版在前一輪基礎上，採納了 provider / extraction 狀態分離與 Majority-claim 判定，
> 並再修掉三個 MECE / 語意邊界 bug：
> **(1) Insufficient(==1) 與 Failure(==0) 必須互斥**；
> **(2) 多軸衝突指向不同 provider 時，不得判 Majority**；
> **(3) `direct_answer = unknown` 視為棄權，不得單獨觸發 Minority Report**。
>
> 本文件是 spec-driven 開發前的交接文件，不是最終規格書。後續 agent 必須先產出 `docs/00..07`，
> 不得直接開始寫 Laravel application code。

---

## 本次修訂重點（Changelog）

| 編號 | 問題 | 本版處理 |
|------|------|----------|
| T1-A | `normalized` 由誰產生未定義 | 拍板：**逐 provider 獨立抽取**，一次只看一家答案（§11、§12） |
| T1-B | claim 對齊與「重大衝突」未定義 | 新增 claim 對齊步驟與逐型別衝突判準（§12.2、§12.3） |
| T1-C | Majority-vs-Minority 在真模型 MVP 幾乎不觸發 | 列為已知限制並定 demo 策略（§3.2、§19） |
| T2-D | Trust Level 不 MECE | 改為 **base + caps 瀑布**，套用全部 cap 後取最嚴格者（§15） |
| T2-E | 1/3 success 說法不一致 | 合併成單一狀態機（§16.5） |
| T2-F | `direct_answer` 裝不下 open 題，造成假 Full Consensus | 引入 `answer_shape`；open 題不走 direct_answer 投票（§9、§13） |
| T2-G | Classifier 是單一 LLM，與專案命題矛盾 | 加 fail-safe bias：不確定時往 C > B > A 靠（§9） |
| T3-H | 「repair」未定義 | MVP 僅做 lenient 本地解析，不 re-prompt（§16.2） |
| T3-I | Evidence Comparison 無 grounding 時像空話 | MVP 只並陳各方說法；裁定來源強弱延後 Phase 3（§14） |
| T3-J | 延遲沒估 | 給出同步預算與 Queue 觸發條件（§17） |
| T3-K | `invalid_response` 混淆 provider 失敗與 extractor JSON 失敗 | 拆成 `provider_status` 與 `extraction_status`（§11、§16） |
| T3-L | open 題 low-discriminability 的 consensus label 不明 | 定義為 `Full (low-discriminability)`，Trust cap 至 Medium（§13、§15、§19） |
| T3-M | Insufficient(<2) 與 Failure(==0) 重疊，0/3 兩者皆中 | 改為互斥：**Insufficient == 1、Failure == 0**（§13 Case 5/6、§16.5） |
| T3-N | 多個重大衝突指向不同 minority owner 時，Case 2/3 互撞 | 加優先序：**全部衝突指向同一 provider 才判 Majority，否則 None**（§12.6、§13 Case 2） |
| T3-O | `direct_answer = unknown` 被當成反對，產出「少數意見是不知道」的荒謬報告 | 定義為**棄權**：計票時先排除，以有效表態數計算（§12.5、§13、§19 Fixture 13） |
| T3-P | Fixture 8 容易被誤解成 MVP 已支援語意對齊 | 明示 Fixture 8 測的是 extractor 的 `canonical_key` 一致性，不是 aligner 語意能力；語意對齊延後 Phase 3（§12.2、§19 Fixture 8） |

---

## 0. 文件目的

本文件是「關鍵報告」專案進入 spec-driven 開發前的 handoff 文件。目的如下：

1. 定義專案目標與 MVP 邊界。
2. 拍板核心技術決策。
3. 明確列出已知限制與失敗模式。
4. 指示後續 agent 先產生正式 spec 文件，而不是直接寫程式碼。

後續開發流程必須先產出：

```text
docs/
├── 00-product-vision.md
├── 01-architecture.md
├── 02-contracts.md
├── 03-consensus-algorithm.md
├── 04-trust-level.md
├── 05-failure-modes.md
├── 06-test-scenarios.md
└── 07-milestones.md
```

在上述文件完成前，不應直接開始實作 Laravel 程式碼。

---

## 1. Project Vision

「關鍵報告」是一套基於 Laravel 的 **Multi-LLM Consensus Engine**。

本專案的目標不是消除所有電子幻覺，而是：

> **降低單一模型幻覺風險，並明確揭露多模型之間的共識、分歧與不確定性。**

靈感來自電影《Minority Report》。電影中不同預測者可能對未來產生不同預測，少數意見具有重要參考價值。
本系統將此概念應用於大型語言模型：當多個模型對同一問題產生不同答案時，系統不應直接忽略少數意見，
而應保留、分析、比對並產生裁決報告。

---

## 2. Core Philosophy

**Disagreement is a feature, not a bug.**

模型間的不一致本身就是資訊。差異可能代表：

* 問題本身有歧義。
* 模型知識截止日不同。
* 搜尋或來源不同。
* 部分模型產生幻覺。
* 問題涉及爭議或尚無明確答案。

因此本系統不只追求「一致答案」，也必須呈現「為什麼不一致」。

---

## 3. Known Limitations

### 3.1 Consensus is not correctness

多個模型同意，不代表答案正確。三個模型可能因共享相似訓練資料、相似網路語料或相似錯誤假設，
而同時產生錯誤答案。因此：

* Consensus 只代表模型群體內部一致，不等同事實正確。
* Trust Level 不得僅依模型一致性給出高分。
* 對時效性問題，必須標記是否需要即時來源或官方文件驗證。

MVP 階段若尚未完成 Web Search / Fact Check，系統**不得對需要即時資料的問題給出 High Trust**。

### 3.2 ✅ 決策：MVP 階段招牌功能主要靠 fake provider 驗證

這是必須在開工前認清的 scoping 事實，否則 demo 會出事：

* **Type A** → 單模型，無 consensus。
* **Type B** 多為穩定概念題，真實三模型幾乎永遠一致，Minority Report 不會自然啟動。
* **Type C** 版本、新聞、股價、法規等真正可能分裂的 factual 題，會被 grounding 規則封頂在 Low / Unknown。

結論：**Majority-vs-Minority 在 MVP 用真模型時幾乎沒有舞台。**

處理方式：

1. MVP 的 Majority / Minority / No Consensus 路徑，主要由 fake provider fixtures 驗證，不依賴真模型自然觸發。
2. demo 若要展示真實分歧，刻意挑選接近模型知識截止日、或業界尚有爭議的 Type B 技術斷言，而非概念定義題。
3. 真實且可靠的分歧觸發，待 Phase 3 接上 grounding 後才成熟。

---

## 4. MVP Scope

MVP 只做一件事：完成 **Question → Verification → Consensus → Verdict** 的最小閉環。

MVP 不追求：完整 Agent Framework、商業化、多租戶、完整 RAG、完整 grounding、完整 semantic alignment。

---

## 5. Non Goals

MVP 階段不做：

* RAG / 向量資料庫。
* Agent Marketplace / MCP Marketplace。
* 多使用者團隊協作。
* 付費機制。
* 複雜工作流編排。
* 多輪辯論。
* 完整匿名互評。
* 自動長期記憶。
* Phase 3 等級的來源可信度裁定。

---

## 6. Technical Stack

* **Framework**：Laravel 12+
* **PHP**：8.4+
* **Database**：MySQL 或 SQLite（MVP 可先用 SQLite）

### AI Provider Layer

不自行實作 provider abstraction。優先順序：

1. Laravel AI SDK
2. Prism
3. 其他 Laravel LLM Client

原因：Laravel 生態已有成熟 provider abstraction；本專案價值不在 provider layer，而在 consensus layer。

選定後須在 `01-architecture.md` 記錄最終採用哪一個，以及其 timeout、重試、並行能力，
因為 §17 的延遲預算依賴它。

---

## 7. Architecture Principle

**Clean boundary, not premature portability.**

MVP 不需一開始就支援 Web、Discord、MCP、Claude Code Skill、Cursor Agent 等多種 surface，
但核心邏輯應放在乾淨的 domain layer，避免 Laravel infrastructure 汙染核心邏輯。

建議結構：

```text
app/
├── Consensus/
│   ├── Contracts/
│   ├── DTO/
│   ├── Classifier/
│   ├── Extractor/      ← 逐 provider 獨立抽取
│   ├── Aligner/        ← 跨 provider claim 對齊
│   ├── Analyzer/
│   ├── Scorer/
│   └── Reporter/
│
├── AI/
│   └── Providers/
│
├── Http/
├── Models/
└── Jobs/
```

規則：

* `app/Consensus` 不直接依賴 Eloquent。
* `app/Consensus` 不直接依賴 Queue。
* `app/Consensus` 不直接依賴 Prism / Laravel AI SDK facade。
* 外部服務透過 interface 注入。
* Laravel 只負責 HTTP、DB、Queue、設定與組裝。

---

## 8. System Workflow

```text
User Question
        │
        ▼
Question Classifier  (含 fail-safe bias，見 §9)
        │
        ▼
Question Type + answer_shape
        │
 ┌──────┼────────────┐
 │      │            │
 A      B            C
 │      │            │
 ▼      ▼            ▼
Single  Multi-LLM    Multi-LLM + Requires Grounding
Answer  Consensus    (MVP 無 grounding → Trust 受 §15 封頂)
        │            │
        └─────┬──────┘
              ▼
   Provider raw answers (parallel)
              ▼
   Per-provider independent extraction
              ▼
   Cross-provider claim alignment
              ▼
   Deterministic comparison + rule-based classification
              ▼
   Trust Level (base + caps)
              ▼
   Verdict Reporter (LLM-assisted; 不負責裁決)
```

---

## 9. Question Classification

Classifier 不只判斷是否啟動多模型驗證，還必須輸出問題類型與 `answer_shape`。

### ✅ 決策：Classifier 輸出契約

```json
{
  "type": "A",
  "answer_shape": "open",
  "requires_grounding": false,
  "classifier_confidence": "high"
}
```

欄位規則：

* `type`：`A | B | C`
* `answer_shape`：`discrete | open`
* `requires_grounding`：boolean
* `classifier_confidence`：`high | low`

`answer_shape = discrete`：有離散明確答案，例如 yes/no、日期、版本號、數字、單一實體。

`answer_shape = open`：概念說明或開放敘述，例如「MVC 是什麼」。open 題不得用 `direct_answer` 投票判斷一致。

### ✅ 決策：Fail-safe bias

分類器是單一 LLM、無交叉驗證，與本專案「別信單一模型」的命題天生矛盾，因此錯誤方向必須被約束：

* 危險方向是「把 Type C 誤判成 B」——會在時效題上錯誤地給出 High Trust。
* 當 `classifier_confidence = low`，分類往保守端靠：**C 優先於 B，B 優先於 A**。
* 寧可多花一次驗證、寧可降 Trust，也不要漏掉 grounding 標記。
* 分類結果與 confidence 必須寫入 audit trail，供日後校準。

### Type A: No Verification Required

無客觀唯一答案，或即使多模型分歧也無法靠 consensus 裁定。適用：創作、個人意見、開放式建議、哲學討論。

範例：

* 我該不該換工作？
* Laravel 和 Symfony 哪個比較好？
* 幫我寫一段自傳。

處理：單一模型回答，不進 consensus workflow。

### Type B: Multi-LLM Verification Required

需多模型驗證，但不一定需即時資料。適用：穩定技術概念、歷史知識、非即時性事實。

範例：

* MVC 是什麼？
* RESTful API 的主要原則？
* Laravel migration 的用途？

處理：啟動多模型驗證，進 consensus workflow。

### Type C: Grounding Required

需即時資料、官方文件、新聞、法規、金融市場資訊或版本資訊。

範例：

* Laravel 最新版本是否支援某功能？
* PHP 8.5 新增哪些特性？
* SpaceX 是否已 IPO？
* 某公司今天股價為何？

處理：

* 仍照常查詢多模型並做 consensus，保留各模型說法供 audit。
* MVP 無 grounding，因此必須標記 `requires_grounding = true`、`grounding_available = false`。
* Trust Level 依 §15 封頂於 Low，不能只憑模型共識給 High。

---

## 10. Provider Strategy

MVP 固定支援三個遠端模型：OpenAI、Anthropic Claude、Google Gemini。

未來可擴充：Ollama、llama.cpp、OpenAI Compatible Endpoint、OpenRouter、本地模型。

規則：

* Provider 回應失敗不得中斷整體流程。
* 每個 provider 的 raw answer 必須獨立保存。
* fake provider 必須是一等公民，用於 fixtures、regression tests、demo replay。

---

## 11. Response Contract

### ✅ 決策：Provider raw answer 與 Extracted normalized DTO 必須分離

流程分成兩步，禁止合併：

1. **Provider 原始回答**：每個 provider 收到相同 prompt，回傳自由文字與可能的自報 citations。
   Provider 不負責產生最終結構化 schema，因為三家自我結構化的切法不會一致，無法穩定比對。
2. **獨立抽取（Extractor）**：對每一個 provider 的答案分別做一次抽取呼叫，轉成 normalized DTO。

鐵則：

> **每次抽取呼叫只能看見一個 provider 的答案。**
>
> 絕不可把多家答案餵進同一次抽取。否則會在抽取階段就把分歧抹平，等於偷塞一個會幻覺、會 homogenize 的單一模型站在 pipeline 咽喉，直接摧毀「各模型獨立」的前提。

### 11.1 統一 DTO

```json
{
  "provider": "openai",
  "model": "gpt-xxx",
  "provider_status": "success",
  "extraction_status": "success",
  "raw_answer": "",
  "normalized": {
    "answer_shape": "discrete",
    "direct_answer": "yes",
    "summary": "",
    "claims": [
      {
        "type": "boolean",
        "canonical_key": "",
        "subject": "",
        "predicate": "",
        "value": "",
        "unit": null,
        "source": null
      }
    ],
    "citations": []
  },
  "usage": {
    "provider_input_tokens": null,
    "provider_output_tokens": null,
    "extractor_input_tokens": null,
    "extractor_output_tokens": null,
    "estimated_cost": null
  },
  "error": null,
  "metadata": {}
}
```

### 11.2 狀態欄位定義

`provider_status` 只描述 provider raw answer 呼叫：

```text
success | failed_timeout | provider_unavailable | provider_error
```

`extraction_status` 只描述 normalized DTO 抽取：

```text
not_started | success | invalid_json | extraction_failed
```

規則：

* Provider 成功但 Extractor 失敗時，`provider_status = success`，`extraction_status = invalid_json | extraction_failed`。
* Consensus 計算只納入 `provider_status = success` 且 `extraction_status = success` 的回答。
* Raw answer 仍要保存，即使 extraction 失敗。
* `invalid_response` 這種含糊狀態不得再使用，除非在正式 spec 中明確 alias 到 `extraction_status = invalid_json`。

### 11.3 `direct_answer` 規則

`direct_answer` 僅在 `answer_shape = discrete` 時具意義。

允許值：

```text
yes | no | unknown | not_applicable
```

open 題一律填 `not_applicable`，且 §13 不得用 direct_answer 判定一致。

規則補充：

* discrete 題若 extractor 無法判定答案，必須填 `unknown`，**不得**填 `not_applicable`。
* `not_applicable` 專屬於 `answer_shape = open`，是「此題型不適用 direct_answer」的標記，不是「不知道」。
* `unknown` 是 discrete 題的「棄權 / 無法判定」，在 §12.5 計票時視為棄權處理。

### 11.4 `canonical_key` 規則

`canonical_key` 由 Extractor 以正規化措辭產生，用於 §12.2 對齊。

範例：

* `php 8.4 release date`
* `laravel migration purpose`
* `spacex ipo status`

---

## 12. Consensus Analyzer

### 12.1 ✅ 決策：不使用純 LLM Judge 作最終裁決者

原因：用第四個模型裁決前三個會引入新的單點幻覺、無法穩定測試、削弱可信度。

MVP 採 Hybrid Analyzer：

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
LLM-assisted report generation
```

LLM 可用於：把自由文字整理成 claims、產生自然語言報告、解釋差異。

LLM **不得**作為唯一裁決者。

### 12.2 ✅ 決策：Claim Alignment

MVP 的對齊規則刻意保守、可測試：

1. 先依 `type` 分組。
2. 同組內，以 `canonical_key` 做正規化字串比對。
3. 正規化流程：小寫、去標點、去多餘空白、去停用詞後相等。
4. 出現在 ≥2 個 provider 的配對 claim，稱為 `aligned claim`，進入 §12.3 比對。
5. 只出現在單一 provider 的 claim，稱為 `unmatched claim`，不計入衝突判定，但必須在報告中 surface。

已知限制：正規化字串比對無法處理語意相同但措辭差距大的 claim。穩健語意對齊（embedding / LLM alignment）延後至 Phase 3。

> 實務提醒：MVP 的 Fixture 8 測到的不是 aligner 具備語意理解能力，而是 extractor 是否能把同義 claim 產生成一致或足夠接近的 `canonical_key`。若 extractor 產出的 `canonical_key` 差距太大，MVP 會保守地視為 unmatched，這是已接受的 Phase 1 失真，不得在 MVP 臨時補上語意對齊。

### 12.3 ✅ 決策：逐型別衝突判準

對 aligned claim，依 `type` 套用確定性規則：

| type | 衝突判準 |
|------|----------|
| `boolean` | 不 exact-match 即衝突 |
| `date` | 正規化為 ISO 後，於雙方共有的最粗粒度比較；不相等即衝突 |
| `number` | 同 `unit` 下相對誤差 > 5% 即衝突；門檻寫入 config |
| `version` | 正規化 semver 後不 exact-match 即衝突 |
| `entity` | MVP 不自動判為衝突，只 surface |
| `source` | MVP 不自動判為衝突，只 surface |
| `statement` | MVP 不自動判為衝突，只 surface |

`unit` 不同且無法換算時，標記 `unalignable`，不計衝突但必須 surface。

**重大 claim 衝突**定義：在 `{boolean, date, number, version}` 四型的 aligned claims 中，存在至少一個衝突。

### 12.4 ✅ 決策：Majority Claim 的判定

對同一個 aligned typed claim，將各 provider 的 normalized value 分組：

* 若存在 `2 vs 1`，且其中一個 provider 的 value 與其他兩個不同，該 provider 是該 claim 的 minority owner。
* 若三個 value 互不相同，該 claim 為 no-majority conflict。
* 若只有兩個 provider 可比較且彼此不同，該 claim 為 1 vs 1 conflict，不產生 Minority Provider。

此規則同時適用 discrete 與 open 題。

### 12.5 ✅ 決策：棄權（abstention）處理

`direct_answer = unknown` 視為**棄權，不是反對**。計算多數時：

* 先排除所有 `direct_answer = unknown` 的 provider，以「有效表態數」計票。
* 有效表態 == 3 → 照常判定。
* 有效表態 == 2：兩者一致 → `Consensus = Full`，但 Trust 依 §15 cap 至 Medium（有效票不足 3）；兩者不一致 → `Consensus = None`。
* 有效表態 < 2 → 比照 §16.5 `Insufficient`。
* **棄權者不得被列為 Minority Provider，也不得單獨觸發 Minority Report。** 「我不知道」不是一份少數意見。

> 範例：`yes / yes / unknown` → 排除棄權後兩者皆 yes → `Consensus = Full`、Trust cap Medium，**不產生 Minority Report**。
> 範例：`yes / no / unknown` → 排除棄權後 1 vs 1 → `Consensus = None`。

### 12.6 ✅ 決策：多軸衝突的 Majority 優先序

一題可能同時在多個軸出現分歧（discrete 的 direct_answer 軸，以及一個或多個 typed claim 軸）。
判定 Majority 必須收斂到**單一** minority owner：

* 唯有**所有重大衝突（含 direct_answer 分歧與所有重大 claim 衝突）都可歸因於同一個 minority provider** 時，才判 `Majority`。
* 若不同衝突指向不同 provider（例：direct_answer 多數方排除 P1，但某重大 claim 衝突的少數方是 P2）→ `Consensus = None`。
* 若任一重大衝突為 no-majority（三者皆異）→ `Consensus = None`。

此優先序高於 §13 Case 2 的個別觸發條件，避免 Case 2 與 Case 3 在多軸情境下互撞。

---

## 13. Consensus Cases

判定輸入：

* 可分析 provider 數：`provider_status = success` 且 `extraction_status = success` 的數量。
* `answer_shape`。
* discrete 題的 `direct_answer`。
* typed aligned claims 的重大衝突結果。
* low-discriminability 判定。

### ✅ 決策：discrete 與 open 走不同主鍵

* `answer_shape = discrete`：一致性主鍵是 `direct_answer`，但仍必須檢查重大 claim 衝突。
* `answer_shape = open`：忽略 `direct_answer`，一致性改看 §12.3 是否存在重大 claim 衝突。

### Low-discriminability 定義

open 題若抽不出任何 `{boolean, date, number, version}` 型 claim，稱為 `low-discriminability`。

此時系統可以說「沒有偵測到可機械比對的重大衝突」，但不能把這種情況當成高可信共識。

### Case 1: Full Consensus

條件：≥3 provider 可分析，且：

* discrete：`direct_answer` 全部相等，且無重大 claim 衝突。
* open：無重大 claim 衝突。

結果：

```text
Consensus = Full
```

若 open 題同時符合 low-discriminability，結果標記為：

```text
Consensus = Full (low-discriminability)
```

Trust 依 §15 cap 至 Medium。

### Case 2: Majority vs Minority

條件：≥3 provider 可分析，且出現明確 `2 vs 1`：

* discrete：一個 provider 的 `direct_answer` 與其他兩個不同。
* claim：某個重大衝突 claim 出現 `2 vs 1`，且可識別 minority owner。

結果：

```text
Consensus = Majority
Minority Provider = provider_name
```

必須產生 Minority Report。

若 direct_answer 一致但重大 claim 出現 2 vs 1，仍判為 Majority，因為 claim 衝突不能被表面答案一致掩蓋。

但須先通過 §12.6 的收斂檢查：**所有重大衝突必須指向同一個 minority provider**。
若 direct_answer 軸與 claim 軸的少數方不是同一個 provider，改判 `Consensus = None`（見 §12.6）。

### Case 3: No Consensus

條件：≥3 provider 可分析，且：

* discrete：三者 `direct_answer` 互不相同。
* claim：存在 no-majority conflict，或多處重大衝突無法歸納出單一 minority owner。

結果：

```text
Consensus = None
```

### Case 4: Two-Provider（2/3 可分析）

條件：恰 2 provider 可分析。

* 兩者一致 → `Consensus = Full (2-only)`，Trust 依 §15 cap 至 Medium。
* 兩者不一致 → `Consensus = None`，Trust ≤ Medium。

2/3 不可能出現 2 對 1 Majority，因此不產生 Minority Report。

### Case 5: Insufficient

條件：可分析 provider **== 1**（恰一個成功）。

結果：

```text
Consensus = Insufficient
```

不得產生高信任裁決。

### Case 6: Failure

條件：可分析 provider **== 0**——provider 全部失敗，或全部 provider 雖有 raw answer 但 extraction 全部失敗。

結果：

```text
Consensus = Failure
Trust = Unknown
```

不產生答案，只產生錯誤報告。

> Case 5 與 Case 6 以「可分析數」嚴格互斥：== 1 為 Insufficient，== 0 為 Failure。
> 兩者皆不得有第三種解讀。

---

## 14. Minority Report Output

當出現 Majority vs Minority 時，輸出必須包含：

```text
Majority Opinion
Minority Opinion
Disputed Claims
Evidence Comparison
Final Verdict
Trust Level
Known Limitations
```

不得直接忽略少數意見，必須保留。

### ✅ 決策：Evidence Comparison 的 MVP 範圍

MVP 沒有外部 grounding 可裁定來源優劣。唯一「證據」是模型自報 citations，而這些 citations 可能是幻覺 URL。

因此 MVP 的 Evidence Comparison 只做：

* 並陳各方說法。
* 並陳各方自報來源。
* 標示來源尚未經外部驗證。

MVP 不得裁定哪一方證據較強。來源品質的實質裁定延後至 Phase 3。

---

## 15. Trust Level

MVP 不使用精確百分比，不輸出 `Trust Score: 92%`。

使用分級：

```text
High | Medium | Low | Unknown
```

排序：

```text
Unknown < Low < Medium < High
```

### ✅ 決策：Base + Caps 瀑布

步驟一，由 consensus 算 base：

| Consensus | base |
|-----------|------|
| Full | High |
| Full (2-only) | High |
| Full (low-discriminability) | High |
| Majority | Medium |
| None | Low |
| Insufficient | Unknown |
| Failure | Unknown |

步驟二，套用所有適用 cap，取最嚴格者：

| 條件 | cap |
|------|-----|
| Type C 且 `grounding_available = false` | Low |
| 可分析 provider 數 == 2 | Medium |
| 有效 direct_answer 表態數 == 2（discrete 題；排除 `unknown` 棄權後） | Medium |
| 存在任何重大 claim 衝突 | Low |
| open 題且 low-discriminability | Medium |
| `Consensus = None` | Low |
| `Consensus = Insufficient` 或 `Failure` | Unknown |

> **兩個獨立計數**：**可分析 provider 數**（`provider_status = success` 且 `extraction_status = success` 的 provider 數）與 **有效 direct_answer 表態數**（discrete 題排除 `direct_answer = unknown` 棄權後的表態數）互不混淆。任一 == 2 時 **MUST** 套用 Medium cap——前者對應 Case 4（2/3 可分析）；後者對應 F13（3 可分析但僅 2 票有效表態）。兩者 **MAY** 同時成立，cap 仍為 Medium。

步驟三：

```text
final_trust = min(base, all_applicable_caps)
```

此設計必須寫成 decision table 測試。

> Phase 3 才能引入「官方來源驗證後提高 trust」的路徑。MVP 不得引用尚未實作的 grounding 給 High Trust。

---

## 16. Failure Modes

以下情況必須定義行為，不得交由 agent 自由發揮。

### 16.1 Provider Timeout

狀態：

```text
provider_status = failed_timeout
extraction_status = not_started
```

處理：保存錯誤；至多重試一次；計入 §16.5 成功數統計時視為不可分析。

### 16.2 Extractor Invalid JSON

狀態：

```text
provider_status = success
extraction_status = invalid_json
```

處理：

* 保存 raw answer。
* repair = 僅做 lenient 本地解析：擷取字串中的 JSON 區段，用容錯解析器嘗試一次。
* MVP 不得 re-prompt extractor 或 provider。
* lenient 解析失敗 → 維持 `invalid_json`，排除於 consensus 計算。

### 16.3 Missing API Key

狀態：

```text
provider_status = provider_unavailable
extraction_status = not_started
```

處理：不呼叫該 provider；記錄設定錯誤；不影響其他 provider。

### 16.4 Provider Error

狀態：

```text
provider_status = provider_error
extraction_status = not_started
```

處理：保存錯誤與 provider metadata；不納入 consensus 計算。

### 16.5 Partial Success — 單一狀態機

本文件中的 success 指「可分析 success」，也就是：

```text
provider_status = success AND extraction_status = success
```

計數規則：

| 可分析 success 數 | Consensus 行為 | Trust 上限 |
|------------------|----------------|------------|
| 3/3 | 正常 consensus | 可達 High，但仍受 caps 限制 |
| 2/3 | Two-Provider case | Medium |
| 1/3 | Insufficient（Case 5） | Unknown |
| 0/3 | Failure（Case 6） | Unknown |

`success == 1`：

* `Consensus = Insufficient`。
* 可選擇把唯一答案以「Single Provider Answer — Unverified」呈現。
* `Trust Level = Unknown`。
* 報告必須標示「未經多模型驗證」。

`success == 0`：

* 若所有 raw provider 也失敗 → `Consensus = Failure`。
* 若有 raw answer 但全數 extraction 失敗 → `Consensus = Failure`，並標示 extraction failure。
* `Trust = Unknown`。
* 不產生最終答案，只產生錯誤報告。

---

## 17. Latency Strategy

以 §11 的逐 provider 獨立抽取架構估算單次 Type B/C 同步路徑：

| 階段 | 呼叫數 | 預估 | 並行化 |
|------|--------|------|--------|
| 分類 | 1 | ~1–3s | — |
| Provider 查詢 | 3 | ~3–10s | 必須並行 |
| 獨立抽取 | 3 | ~3–8s | 應並行 |
| 報告生成 | 1 | ~3–8s | — |

並行化後同步路徑約 **10–25s**，尖峰可能 **> 30s**。

MVP 採同步流程，降低複雜度、方便本地測試，但 provider 查詢與抽取必須並行，否則很容易超過 30 秒。

若實測單次 > 30 秒成常態，Phase 2 改為：

```text
Job dispatch → Polling → Result page
```

MVP 不先實作完整 job polling UI。

---

## 18. Audit Trail

所有流程必須可追蹤、可重播、可除錯。必須保存：

* user question
* classified type
* classifier confidence
* answer_shape
* requires_grounding
* grounding_available
* provider prompts
* raw provider responses
* provider_status
* extraction prompt / extractor model
* extraction_status
* normalized responses
* claims 與 canonical_key
* claim alignment 結果：aligned / unmatched / unalignable
* conflict detection 結果
* consensus result
* 判定走 discrete 或 open 主鍵
* trust level base
* 套用過的 caps
* final trust level
* final verdict
* errors
* timestamps

目的：debug、fixture generation、regression testing、demo replay。

---

## 19. Testing Requirements

spec-driven 開發必須先建立測試場景。fixtures 以 fake provider 注入，不依賴真模型。

### Fixture 1: Full Consensus（discrete）

輸入：三 provider `direct_answer = yes`，無 claim 衝突。

期望：`Consensus = Full`、`Trust = High`。

### Fixture 2: Majority vs Minority（discrete）

輸入：P1=yes、P2=yes、P3=no。

期望：`Consensus = Majority`、`Minority Provider = P3`、產生 Minority Report、`Trust = Medium`。

### Fixture 3: No Consensus（discrete）

輸入：P1=yes、P2=no、P3=unknown。

期望：`Consensus = None`、`Trust = Low`。

### Fixture 4: Type C Without Grounding

輸入：詢問最新版本、新聞、股價、法規、上市時間。

期望：`type = C`、`requires_grounding = true`、`grounding_available = false`、Trust 經 cap 後 ≤ Low，不得輸出 High。

### Fixture 5: Provider Timeout（2/3）

輸入：三 provider 一個 timeout，其餘兩個一致。

期望：timeout provider 記為 `provider_status = failed_timeout`；`Consensus = Full (2-only)`；`Trust ≤ Medium`。

### Fixture 6: Extractor Invalid JSON

輸入：某 provider raw answer 成功，但 extractor 產生無法解析的 JSON。

期望：`provider_status = success`、`extraction_status = invalid_json`；做一次 lenient 本地解析；失敗則排除於 consensus；不得 re-prompt。

### Fixture 7: direct_answer 一致但 typed claim 衝突

輸入：P1/P2/P3 `direct_answer = yes`，但 P3 的某 `date` claim 與 P1/P2 不同，且該 claim 在三方均 aligned。

期望：依 §12.3 判為重大衝突；`Consensus = Majority`、`Minority Provider = P3`；Trust 因衝突 cap 至 Low。

### Fixture 8: open 題、claim 需跨措辭對齊

輸入：`answer_shape = open`，例如「Laravel migration 的用途」，三方 claims 的 `canonical_key` 正規化後可配對，且無重大衝突。

期望：走 open 主鍵，忽略 direct_answer；`Consensus = Full`；驗證 extractor 是否能產生一致或足夠接近的 `canonical_key`，使 §12.2 的字串對齊成功。

注意：此 fixture **不是**測試 aligner 具備語意對齊能力。若三方 `canonical_key` 措辭差距太大而無法正規化相等，MVP 允許保守地判為 unmatched；語意對齊屬 Phase 3。

### Fixture 9: open 題、low-discriminability

輸入：`answer_shape = open`，但抽不出任何 `{boolean,date,number,version}` 型 claim。

期望：`Consensus = Full (low-discriminability)`；Trust cap 至 Medium；報告必須標示「只能確認沒有可機械比對的重大衝突」。

### Fixture 10: 1/3 success

輸入：兩 provider 不可分析，僅一個可分析成功。

期望：`Consensus = Insufficient`；`Trust = Unknown`；可呈現單一答案但標示「Unverified」。

### Fixture 11: Provider raw success but extraction failed for all

輸入：三個 provider 都有 raw answer，但三個 extraction 全部失敗。

期望：`Consensus = Failure`；`Trust = Unknown`；不產生最終答案，只產生 extraction failure report。

### Fixture 12: Two-provider conflict

輸入：恰 2 provider 可分析，且兩者有重大 claim 衝突。

期望：`Consensus = None`；不產生 Minority Report；`Trust ≤ Medium`，若重大衝突 cap 生效則 Low。

### Fixture 13: 棄權不觸發 Minority Report

輸入：discrete 題，P1 `direct_answer = yes`、P2 `yes`、P3 `unknown`，無重大 claim 衝突。

期望：依 §12.5 排除 P3 棄權後兩者皆 yes → `Consensus = Full`；Trust cap 至 Medium（有效票 == 2）；
**不得**把 P3 列為 Minority Provider，**不得**產生 Minority Report。

（對照 Fixture 3 `yes/no/unknown`：排除棄權後 1 vs 1 → 仍為 `Consensus = None`，行為一致。）

### Fixture 14: 多軸衝突指向不同 provider

輸入：≥3 provider 可分析。direct_answer 為 `yes/yes/no`（P3 在答案軸是少數），
但另有一個重大 `date` claim 衝突的少數方是 **P2**（非 P3）。

期望：依 §12.6 收斂檢查，兩軸少數方不一致 → `Consensus = None`（不得誤判為 Majority）；
報告須並陳兩處衝突；Trust 因重大衝突 cap 至 Low。

---

## 20. External References

可參考方向：LLM Council、Duh、Star Chamber、Multi LLM Cross Check、ReConcile。

僅參考流程設計、分類策略、共識分級、少數意見保留、收斂與失敗模式概念。

**不得直接複製受限制授權專案程式碼。** 借鏡概念與架構，自行實作。

特別建議精讀：

* Duh 的 domain-capped confidence 與收斂偵測。
* Star Chamber 的各 provider 獨立審查、回傳結構化 JSON、依共識分級設計。

---

## 21. Milestone Plan

### Milestone 1: Spec Documents

產出 `docs/00..07`。不得寫 application code。

### Milestone 2: Laravel Skeleton

Laravel project、basic routes、config、migration、model skeleton、service skeleton。

### Milestone 3: Provider Integration

`LlmProvider` interface、OpenAI / Claude / Gemini adapter、fake provider for tests。

fake provider 優先，因為 consensus logic 必須先被 fixtures 驗證。

### Milestone 4: Consensus Engine

Question Classifier、Response Extractor、Claim Aligner、Consensus Analyzer、Trust Level Scorer、Verdict Reporter。

### Milestone 5: Audit Trail

request record、provider response record、extraction result record、consensus result record、replay mechanism。

### Milestone 6: Minimal UI

question input、verification result page、provider response comparison、final verdict display。

---

## 22. Success Criteria

MVP 成功條件：

1. 能分類問題為 Type A / B / C，並輸出 answer_shape 與 fail-safe 行為。
2. 能使用 fake provider 跑完整 consensus workflow。
3. 能處理 3/3、2/3、1/3、0/3 可分析情境。
4. 能區分 provider raw failure 與 extractor normalized failure。
5. 能辨識 Full Consensus（discrete 與 open 兩種主鍵）。
6. 能辨識 Full (low-discriminability)，並正確限制 Trust。
7. 能辨識 Majority vs Minority，包含「direct_answer 一致但 claim 衝突」情境。
8. 能依 §12.5 將 `unknown` 視為棄權，不誤產生 Minority Report。
9. 能依 §12.6 在多軸衝突指向不同 provider 時正確改判 No Consensus。
10. 能產生 Minority Report。
11. 能辨識 No Consensus。
12. 能依 §15 瀑布輸出 Trust Level，且結果可由 decision table 重現。
13. 能保存完整 audit trail，含 alignment 結果、extraction status 與套用的 caps。
14. 能使用 Fixture 1–14 進行 regression tests。
15. 能明確區分 MVP 的字串型 claim alignment 與 Phase 3 的 semantic alignment，不得在 Fixture 8 中暗示 MVP 已具備語意對齊。

---

## 23. Instruction for Cursor / Agent

請根據本文件先產出正式 spec documents。**不得直接開始撰寫 Laravel application code。**

spec 撰寫優先順序：

1. `02-contracts.md`：DTO、狀態欄位、interface；落實 §11 provider / extractor 分離。
2. `03-consensus-algorithm.md`：落實 §12 對齊、衝突判準、§13 cases。
3. `05-failure-modes.md`：落實 §16 狀態機。
4. `06-test-scenarios.md`：落實 §19 Fixtures 1–14。
5. `04-trust-level.md`：落實 §15 base + caps，寫成 decision table。
6. `01-architecture.md`：含 §6 provider 選型、§17 延遲與並行決策。
7. `00-product-vision.md`、`07-milestones.md`：整理願景與開發順序。

完成 spec 後，再提出 Laravel 實作計畫。

請特別避免：

* 使用單一 LLM Judge 直接裁決。
* 把多家答案餵進同一次抽取呼叫。
* 混用 provider raw failure 與 extractor JSON failure。
* 未定義公式就產生精確百分比 Trust Score。
* 用重疊 OR 清單寫 Trust Level；必須是 base + caps 瀑布。
* 把 open 題的全體 `not_applicable` 當成 Full Consensus。
* 把 `direct_answer = unknown` 當成反對票或少數意見（它是棄權，見 §12.5）。
* 讓 Insufficient 與 Failure 的條件重疊（必須 == 1 / == 0 嚴格互斥）。
* 在多軸衝突指向不同 provider 時誤判為 Majority（必須收斂到單一 minority owner，見 §12.6）。
* 忽略 provider failure 或 extraction failure。
* 將 Consensus 誤當成 Correctness。
* 在 MVP 階段引入過度複雜架構。
* 提前做 Phase 3 的 grounding、來源裁定、語意對齊或完整 RAG。
