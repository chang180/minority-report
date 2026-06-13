# M2-B Progress — Consensus / AI 骨架

| 欄位 | 值 |
|------|-----|
| Gate | **M2-B** |
| 狀態 | **OPEN** |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 交付物檢核

### 1.1 目錄結構

- [ ] `app/Consensus/Contracts/`
- [ ] `app/Consensus/DTO/`
- [ ] `app/Consensus/Classifier/`
- [ ] `app/Consensus/Extractor/`
- [ ] `app/Consensus/Aligner/`
- [ ] `app/Consensus/Analyzer/`
- [ ] `app/Consensus/Scorer/`
- [ ] `app/Consensus/Reporter/`
- [ ] `app/AI/Providers/`

### 1.2 Interface（`app/Consensus/Contracts/`）

- [ ] `QuestionClassifier.php`
- [ ] `LlmProvider.php`
- [ ] `ResponseExtractor.php`
- [ ] `ClaimAligner.php`
- [ ] `ConsensusAnalyzer.php`
- [ ] `TrustLevelScorer.php`
- [ ] `VerdictReporter.php`
- [ ] `FakeProviderRegistry.php`（或等價位置，須有 interface）

### 1.3 DTO 骨架（`app/Consensus/DTO/`）

- [ ] `Question.php`
- [ ] `ClassificationResult.php`
- [ ] `ProviderResponse.php`
- [ ] `ConsensusResult.php`
- [ ] `TrustLevelResult.php`
- [ ] `VerdictReport.php`
- [ ] `AlignmentResult.php`
- [ ] `AnalysisContext.php`
- [ ] `VerdictInput.php`

### 1.4 對照 docs/02-contracts.md §9

- [ ] 各 interface 方法簽名與 spec 一致（無業務實作）

### 1.5 禁止項

- [ ] **無** consensus / trust / align 算法實作
- [ ] **無** LLM / Laravel AI SDK 呼叫
- [ ] **無** migration（M2-D）
- [ ] **無** `config/consensus.php`（M2-C）

---

## 2. 驗收命令

```bash
composer dump-autoload
php -r "require 'vendor/autoload.php'; class_exists(App\\Consensus\\Contracts\\LlmProvider::class) || exit(1); echo 'ok';"
php artisan --version
```

### 2.1 輸出紀錄

```text
（Worker 貼上）
```

---

## 3. 變更檔案清單

```text
（Worker 列出）
```

---

## 4. Worker 提交

| 項目 | 內容 |
|------|------|
| 提交者 | |
| 日期 | |
| 02 §9 對照 | ☐ 通過 |
| 留給 M2-C | |
| **建議 Orchestrator 文件更新** | |

---

## 5. Orchestrator 審核

| 結果 | ☐ RELEASED · ☐ REJECTED |
| **docs / README 整合** | ☐ 已更新 · ☐ N/A |
| Blocking | |
| 日期 | |
