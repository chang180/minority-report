# 02 — Contracts（資料契約與 Interface）

本文件定義「關鍵報告」MVP 的結構化資料契約、狀態枚舉與 PHP Interface 草案。術語以本文件為 **canonical** 來源。

---

## 1. 設計原則

1. Provider raw answer 與 Extracted normalized DTO **MUST** 分離（兩步流程，禁止合併）。
2. 每次 Extractor 呼叫 **MUST NOT** 同時看見多個 provider 的答案。
3. `provider_status` 與 `extraction_status` **MUST** 獨立描述，不得混用 `invalid_response` 等含糊狀態。
4. Consensus 計算 **MUST** 只納入可分析 success 的回答。

---

## 2. Question Classifier 輸出契約

### 2.1 Schema

```json
{
  "type": "A",
  "answer_shape": "open",
  "requires_grounding": false,
  "classifier_confidence": "high"
}
```

### 2.2 欄位規則

| 欄位 | 型別 | 允許值 | 說明 |
|------|------|--------|------|
| `type` | string | `A` \| `B` \| `C` | 問題類型 |
| `answer_shape` | string | `discrete` \| `open` | 答案形狀 |
| `requires_grounding` | boolean | `true` \| `false` | 是否需要即時/外部 grounding |
| `classifier_confidence` | string | `high` \| `low` | 分類器對自身判斷的信心 |

- `answer_shape = discrete`：有離散明確答案（yes/no、日期、版本號、數字、單一實體）。
- `answer_shape = open`：概念說明或開放敘述（例如「MVC 是什麼」）。open 題 **MUST NOT** 使用 `direct_answer` 投票判斷一致（見 [03-consensus-algorithm.md](03-consensus-algorithm.md)）。

### 2.3 問題類型

| Type | 說明 | 處理 |
|------|------|------|
| A | 無客觀唯一答案；創作、意見、哲學討論 | 單一模型，不進 consensus workflow |
| B | 穩定技術概念、歷史知識、非即時事實 | 多模型驗證，進 consensus workflow |
| C | 需即時資料、官方文件、新聞、法規、金融、版本 | 多模型 + `requires_grounding = true`；MVP 無 grounding 時 Trust 受 cap |

Type C 處理時 **MUST** 設定 `requires_grounding = true`；MVP **MUST** 同時標記 `grounding_available = false`（執行期欄位，見 Audit Trail）。

### 2.4 Fail-safe Bias

分類器為單一 LLM，錯誤方向 **MUST** 被約束：

- 危險方向：把 Type C 誤判成 B（時效題錯誤 High Trust）。
- 當 `classifier_confidence = low`，分類 **MUST** 往保守端靠：**C 優先於 B，B 優先於 A**。
- 寧可多花驗證、寧可降 Trust，也不要漏掉 grounding 標記。
- 分類結果與 confidence **MUST** 寫入 audit trail。
- Fail-safe **SHOULD** 實作為 LLM 輸出後的 **deterministic 後處理**（不依賴真模型即可單元測試）。回歸測試見 [06-test-scenarios.md §4](06-test-scenarios.md) CT-G1–G3。

---

## 3. Provider Response DTO

### 3.1 完整 Schema

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

### 3.2 頂層欄位

| 欄位 | 說明 |
|------|------|
| `provider` | Provider 識別：`openai` \| `anthropic` \| `gemini` \| `fake` 等 |
| `model` | 實際模型 ID |
| `provider_status` | Raw answer 呼叫狀態（§4.1） |
| `extraction_status` | Normalized 抽取狀態（§4.2） |
| `raw_answer` | Provider 自由文字；**MUST** 保存即使 extraction 失敗 |
| `normalized` | 結構化 DTO；僅在 `extraction_status = success` 時完整有效 |
| `usage` | Token 與成本統計 |
| `error` | 錯誤詳情（nullable） |
| `metadata` | 擴充欄位 |

### 3.3 Normalized 子物件

| 欄位 | 說明 |
|------|------|
| `answer_shape` | 與 classifier 一致：`discrete` \| `open` |
| `direct_answer` | 見 §5 |
| `summary` | 答案摘要 |
| `claims` | 結構化 claim 陣列 |
| `citations` | 模型自報引用（可能為幻覺 URL） |

---

## 4. 狀態欄位定義

### 4.1 `provider_status`

只描述 provider raw answer 呼叫：

```text
success | failed_timeout | provider_unavailable | provider_error
```

| 值 | 語意 |
|----|------|
| `success` | Raw answer 取得成功 |
| `failed_timeout` | 逾時；至多重試一次 |
| `provider_unavailable` | 缺 API key 等設定問題；不呼叫 |
| `provider_error` | 其他 provider 層錯誤 |

當 `provider_status != success` 時，`extraction_status` **MUST** 為 `not_started`。

### 4.2 `extraction_status`

只描述 normalized DTO 抽取：

```text
not_started | success | invalid_json | extraction_failed
```

| 值 | 語意 |
|----|------|
| `not_started` | Provider 未成功，尚未抽取 |
| `success` | 抽取成功，normalized 可用 |
| `invalid_json` | Extractor 回傳無法解析的 JSON；可嘗試 lenient 本地 repair 一次 |
| `extraction_failed` | 抽取邏輯失敗（非 JSON 格式問題） |

### 4.3 狀態組合規則

- Provider 成功但 Extractor 失敗：`provider_status = success`，`extraction_status = invalid_json | extraction_failed`。
- 可分析 success **MUST** 同時滿足：`provider_status = success` AND `extraction_status = success`。
- `invalid_response` **MUST NOT** 作為正式狀態；若 legacy 資料存在，**SHOULD** alias 至 `extraction_status = invalid_json`。

---

## 5. `direct_answer` 規則

`direct_answer` 僅在 `answer_shape = discrete` 時具投票語意。

### 5.1 允許值

```text
yes | no | unknown | not_applicable
```

### 5.2 語意

| 值 | 適用 | 語意 |
|----|------|------|
| `yes` | discrete | 肯定 |
| `no` | discrete | 否定 |
| `unknown` | discrete | 棄權 / 無法判定；計票時排除，**MUST NOT** 觸發 Minority Report |
| `not_applicable` | open | 此題型不適用 direct_answer；**不是**「不知道」 |

規則：

- discrete 題若 extractor 無法判定，**MUST** 填 `unknown`，**MUST NOT** 填 `not_applicable`。
- open 題 **MUST** 填 `not_applicable`。
- open 題 **MUST NOT** 使用 `direct_answer` 判定 consensus（見 03）。

---

## 6. Claim 型別與結構

### 6.1 Claim 物件

```json
{
  "type": "boolean",
  "canonical_key": "laravel migration purpose",
  "subject": "",
  "predicate": "",
  "value": "",
  "unit": null,
  "source": null
}
```

### 6.2 Claim 型別

| type | MVP 衝突判定 |
|------|-------------|
| `boolean` | 自動判定衝突 |
| `date` | 自動判定衝突 |
| `number` | 自動判定衝突（同 unit，>5% 相對誤差） |
| `version` | 自動判定衝突 |
| `entity` | 只 surface，不自動判衝突 |
| `source` | 只 surface |
| `statement` | 只 surface |

**重大 claim** 指 `{boolean, date, number, version}` 四型。

---

## 7. `canonical_key` 規則

- **MUST** 由 Extractor 以正規化措辭產生，供跨 provider 字串對齊。
- 正規化流程（aligner 使用）：小寫、去標點、去多餘空白、去停用詞後比對相等。
- 範例：`php 8.4 release date`、`laravel migration purpose`、`spacex ipo status`。

MVP **MUST NOT** 依賴 embedding 或 LLM 語意對齊；語意相同但 `canonical_key` 差距過大時 **SHOULD** 保守視為 unmatched。

---

## 8. Provider 策略

MVP **SHOULD** 支援三個遠端 backend：OpenAI、Anthropic Claude、Google Gemini。

規則：

- 單一 provider 失敗 **MUST NOT** 中斷整體流程。
- 每個 provider 的 raw answer **MUST** 獨立保存。
- fake provider **MUST** 為一等公民（fixtures、regression、demo replay）。

### 8.1 AI Infrastructure 邊界

- Domain 契約：`LlmProvider`、`QuestionClassifier`、`ResponseExtractor` 等（§9）。
- Infrastructure：`app/AI/` 使用 **Laravel AI SDK** 把各家 API bridge 成 domain interface。
- **MUST NOT** 在 `app/Consensus` 內直接依賴 SDK；後續換 backend **SHOULD** 只改 adapter。
- 見 [01-architecture.md §4](01-architecture.md)。

---

## 9. PHP Interface 草案

以下為 domain layer 契約，**不含** Laravel / Eloquent / SDK 實作。

```php
<?php

namespace App\Consensus\Contracts;

use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;

interface QuestionClassifier
{
    /**
     * @throws ClassifierException
     */
    public function classify(Question $question): ClassificationResult;
}

interface LlmProvider
{
    public function name(): string;

    /**
     * @throws ProviderException on provider_status != success paths
     */
    public function ask(Question $question, string $prompt): ProviderResponse;
}

interface ResponseExtractor
{
    /**
     * Extract normalized DTO from a SINGLE provider's raw answer.
     * MUST NOT receive answers from multiple providers.
     */
    public function extract(
        ProviderResponse $providerResponse,
        ClassificationResult $classification,
    ): ProviderResponse;
}

interface ClaimAligner
{
    /**
     * @param ProviderResponse[] $analyzableResponses
     */
    public function align(array $analyzableResponses): AlignmentResult;
}

interface ConsensusAnalyzer
{
    /**
     * @param ProviderResponse[] $analyzableResponses
     */
    public function analyze(
        ClassificationResult $classification,
        array $analyzableResponses,
        AlignmentResult $alignment,
    ): ConsensusResult;
}

interface TrustLevelScorer
{
    public function score(
        ClassificationResult $classification,
        ConsensusResult $consensus,
        AnalysisContext $context,
    ): TrustLevelResult;
}

interface VerdictReporter
{
    /**
     * LLM-assisted narrative only; MUST NOT override deterministic consensus.
     */
    public function report(VerdictInput $input): VerdictReport;
}

interface FakeProviderRegistry
{
    public function register(string $fixtureId, callable $behavior): void;

    public function create(string $fixtureId): LlmProvider;
}
```

支援 DTO（`ClassificationResult`、`ProviderResponse`、`ConsensusResult` 等）**MUST** 映射 §2–§7 schema，實作細節留待 Milestone 4。

---

## 10. Audit Trail 欄位

所有流程 **MUST** 可追蹤、可重播。以下欄位 **MUST** 保存：

| 欄位 | 寫入 Stage |
|------|------------|
| user question | Request |
| classified type | Classifier |
| classifier confidence | Classifier |
| answer_shape | Classifier |
| requires_grounding | Classifier |
| grounding_available | Runtime（MVP 固定 false） |
| provider prompts | Provider |
| raw provider responses | Provider |
| provider_status | Provider |
| extraction prompt / extractor model | Extractor |
| extraction_status | Extractor |
| normalized responses | Extractor |
| claims 與 canonical_key | Extractor |
| claim alignment 結果（aligned / unmatched / unalignable） | Aligner |
| conflict detection 結果 | Analyzer |
| consensus result | Analyzer |
| 判定走 discrete 或 open 主鍵 | Analyzer |
| trust level base | Scorer |
| 套用過的 caps | Scorer |
| final trust level | Scorer |
| final verdict | Reporter |
| errors | 各 stage |
| timestamps | 各 stage |

目的：debug、fixture generation、regression testing、demo replay。

### 10.1 M7 擴充欄位

| 欄位 | 寫入 Stage | 說明 |
|------|------------|------|
| `user_id` | Request | nullable；訪客 demo 為 null |
| `metadata.source` | Request | `demo` \| `authenticated` |
| `metadata.demo_mode` | Request | demo 時：`fake_fixtures` \| `shared_local_api` |

**MUST NOT** 將 API key、token 寫入 audit 或 metadata。

---

## 11. Identity 與 Provider 憑證（M7+）

應用層契約詳見 [08-ui-auth-providers.md](08-ui-auth-providers.md)。摘要：

- 三 **consensus slot** 邏輯名：`openai`、`anthropic`、`gemini`（§8 不變）
- **preset**：對應 `config/ai.php` driver + user `api_key`（+ optional `api_url`）
- **custom**：user 自訂 `label`、`api_url`、`api_key`
- `LlmProvider[]` **MUST** 由 `app/AI/` factory 依 User 或 DemoSettings 組裝後注入 `ConsensusWorkflow`

---

## Traceability

| 本文件章節 | description.md |
|------------|----------------|
| §2 Classifier | §9 |
| §3–§4 Provider / 狀態 | §11 |
| §5 direct_answer | §11.3, §12.5 |
| §6–§7 Claims / canonical_key | §11.4, §12.2 |
| §8 Provider | §10 |
| §9 Interfaces | §7 |
| §10 Audit Trail | §18 |
| §11 Identity / Provider 憑證 | [08-ui-auth-providers.md](08-ui-auth-providers.md) |

**技術決策覆寫**：Framework 見 [.ai-dev/planning/plan.md](../.ai-dev/planning/plan.md)（Laravel 13）；description §6 寫 Laravel 12+ 以本專案 plan 為準。
