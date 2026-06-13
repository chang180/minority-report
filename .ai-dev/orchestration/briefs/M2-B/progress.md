# M2-B Progress — Consensus / AI 骨架

| 欄位 | 值 |
|------|-----|
| Gate | **M2-B** |
| 狀態 | **RELEASED** |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 交付物檢核

### 1.1 目錄結構

- [x] `app/Consensus/Contracts/`
- [x] `app/Consensus/DTO/`
- [x] `app/Consensus/Classifier/`
- [x] `app/Consensus/Extractor/`
- [x] `app/Consensus/Aligner/`
- [x] `app/Consensus/Analyzer/`
- [x] `app/Consensus/Scorer/`
- [x] `app/Consensus/Reporter/`
- [x] `app/AI/Providers/`

### 1.2 Interface（`app/Consensus/Contracts/`）

- [x] `QuestionClassifier.php`
- [x] `LlmProvider.php`
- [x] `ResponseExtractor.php`
- [x] `ClaimAligner.php`
- [x] `ConsensusAnalyzer.php`
- [x] `TrustLevelScorer.php`
- [x] `VerdictReporter.php`
- [x] `FakeProviderRegistry.php`（或等價位置，須有 interface）

### 1.3 DTO 骨架（`app/Consensus/DTO/`）

- [x] `Question.php`
- [x] `ClassificationResult.php`
- [x] `ProviderResponse.php`
- [x] `ConsensusResult.php`
- [x] `TrustLevelResult.php`
- [x] `VerdictReport.php`
- [x] `AlignmentResult.php`
- [x] `AnalysisContext.php`
- [x] `VerdictInput.php`

### 1.4 對照 docs/02-contracts.md §9

- [x] 各 interface 方法簽名與 spec 一致（無業務實作）

### 1.5 禁止項

- [x] **無** consensus / trust / align 算法實作
- [x] **無** LLM / Laravel AI SDK **呼叫**（`laravel/ai` 已在 M2-A 安裝）
- [x] **無** migration（M2-D；agent conversation 表除外，屬 M2-A）
- [x] **無** `config/consensus.php`（M2-C）

---

## 2. 驗收命令

```bash
composer dump-autoload
php -r "require 'vendor/autoload.php'; class_exists(App\\Consensus\\Contracts\\LlmProvider::class) || exit(1); echo 'ok';"
php artisan --version
```

### 2.1 輸出紀錄

```text
$ composer dump-autoload
Generating optimized autoload files
... package discovery OK ...
Generated optimized autoload files containing 9583 classes

$ php -r "require 'vendor/autoload.php'; interface_exists(App\Consensus\Contracts\LlmProvider::class) || exit(1); echo 'ok';"
ok

$ php artisan --version
Laravel Framework 13.15.0

$ vendor/bin/pint --dirty --format agent
{"tool":"pint","result":"passed"}

$ php -l app/Consensus/**/*.php
No syntax errors detected in all generated PHP files.

$ php artisan test --compact
Tests: 2 passed (11 assertions)
```

> Note: brief 的驗收命令使用 `class_exists(App\\Consensus\\Contracts\\LlmProvider::class)`，但 `LlmProvider` 依 02 §9 必須是 PHP `interface`；PHP 對 interface 應使用 `interface_exists()`。已用 `interface_exists()` 驗證 autoload。

---

## 3. 變更檔案清單

```text
新增:
  app/AI/Providers/.gitkeep
  app/Consensus/Aligner/.gitkeep
  app/Consensus/Analyzer/.gitkeep
  app/Consensus/Classifier/.gitkeep
  app/Consensus/Extractor/.gitkeep
  app/Consensus/Reporter/.gitkeep
  app/Consensus/Scorer/.gitkeep
  app/Consensus/Contracts/ClaimAligner.php
  app/Consensus/Contracts/ConsensusAnalyzer.php
  app/Consensus/Contracts/FakeProviderRegistry.php
  app/Consensus/Contracts/LlmProvider.php
  app/Consensus/Contracts/QuestionClassifier.php
  app/Consensus/Contracts/ResponseExtractor.php
  app/Consensus/Contracts/TrustLevelScorer.php
  app/Consensus/Contracts/VerdictReporter.php
  app/Consensus/DTO/AlignmentResult.php
  app/Consensus/DTO/AnalysisContext.php
  app/Consensus/DTO/ClassificationResult.php
  app/Consensus/DTO/ConsensusResult.php
  app/Consensus/DTO/ProviderResponse.php
  app/Consensus/DTO/Question.php
  app/Consensus/DTO/TrustLevelResult.php
  app/Consensus/DTO/VerdictInput.php
  app/Consensus/DTO/VerdictReport.php

修改:
  .ai-dev/orchestration/briefs/M2-B/progress.md
```

---

## 4. Worker 提交

| 項目 | 內容 |
|------|------|
| 提交者 | Worker Agent（Codex） |
| 日期 | 2026-06-13 |
| 02 §9 對照 | ☑ 通過 |
| 留給 M2-C | `config/consensus.php` 業務綁定、interface-to-implementation binding、provider fixture 設定。 |
| **建議 Orchestrator 文件更新** | brief 驗收命令對 interface 應改用 `interface_exists()`，或改查 DTO class；本 gate 未改 `docs/` / 根 `README.md`。 |

### 4.1 docs/02-contracts.md §9 對照表

| Interface | 實作檔案 | 簽名狀態 |
|------|------|------|
| `QuestionClassifier` | `app/Consensus/Contracts/QuestionClassifier.php` | `classify(Question $question): ClassificationResult` |
| `LlmProvider` | `app/Consensus/Contracts/LlmProvider.php` | `name(): string`; `ask(Question $question, string $prompt): ProviderResponse` |
| `ResponseExtractor` | `app/Consensus/Contracts/ResponseExtractor.php` | `extract(ProviderResponse $providerResponse, ClassificationResult $classification): ProviderResponse` |
| `ClaimAligner` | `app/Consensus/Contracts/ClaimAligner.php` | `align(array $analyzableResponses): AlignmentResult` |
| `ConsensusAnalyzer` | `app/Consensus/Contracts/ConsensusAnalyzer.php` | `analyze(ClassificationResult $classification, array $analyzableResponses, AlignmentResult $alignment): ConsensusResult` |
| `TrustLevelScorer` | `app/Consensus/Contracts/TrustLevelScorer.php` | `score(ClassificationResult $classification, ConsensusResult $consensus, AnalysisContext $context): TrustLevelResult` |
| `VerdictReporter` | `app/Consensus/Contracts/VerdictReporter.php` | `report(VerdictInput $input): VerdictReport` |
| `FakeProviderRegistry` | `app/Consensus/Contracts/FakeProviderRegistry.php` | `register(string $fixtureId, callable $behavior): void`; `create(string $fixtureId): LlmProvider` |

---

## 5. Orchestrator 審核

| 項目 | 內容 |
|------|------|
| 審核者 | Orchestrator |
| 日期 | 2026-06-13 |
| 結果 | ☑ **RELEASED** · ☐ REJECTED |
| **docs / README 整合** | ☑ N/A（Worker 未改 docs/README） |
| Blocking | 無 |
| 備註 | 獨立重跑驗收：`interface_exists(LlmProvider)` OK、Laravel 13.15.0、`test` 2 passed。8 interface + 9 DTO 與 02 §9 簽名一致；無 implements 類別、無 `config/consensus.php`、無 SDK 引用。已採 Worker 建議修正 brief 驗收為 `interface_exists()`。下一 Gate：**M2-C**。 |
