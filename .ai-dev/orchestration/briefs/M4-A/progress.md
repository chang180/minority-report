# M4-A Progress — Classifier + Extractor

| 欄位 | 值 |
|------|-----|
| Gate | **M4-A** |
| 狀態 | **RELEASED** |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 交付物檢核

- [x] `QuestionClassifier` 實作：`FailSafeQuestionClassifier`
- [x] CT-G1–G3 fail-safe bias deterministic 單元測試
- [x] `ResponseExtractor` 實作：`JsonResponseExtractor`
- [x] Extractor 逐 provider 獨立呼叫：`ResponseExtractionOrchestrator`
- [x] 更新 `provider_responses.extraction_status` / `normalized` / extraction audit 欄位
- [x] DI wiring：`ConsensusServiceProvider`
- [x] fake provider / fixture replay 路徑可解析 normalized JSON

### 1.1 禁止項

- [x] 未修改 `docs/`、根目錄 `README.md`
- [x] 未實作 Aligner / Analyzer / Trust / Verdict
- [x] 未把多家答案餵進同一次 Extractor 呼叫
- [x] `app/Consensus/` 內無 Laravel AI SDK 直接引用

---

## 2. 驗收命令

```bash
php artisan test --compact --filter=FailSafeBias
php artisan test --compact --filter=JsonResponseExtractor
php artisan test --compact --filter=ResponseExtractionPersistence
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

### 2.1 輸出紀錄

```text
$ php artisan test --compact --filter=FailSafeBias
Tests: 4 passed (14 assertions)

$ php artisan test --compact --filter=JsonResponseExtractor
Tests: 4 passed (16 assertions)

$ php artisan test --compact --filter=ResponseExtractionPersistence
Tests: 3 passed (12 assertions)

$ php artisan test --compact
Tests: 1 skipped, 30 passed (170 assertions)

$ vendor/bin/pint --dirty --format agent
{"tool":"pint","result":"passed"}
```

> Skipped test: existing opt-in live OpenAI adapter test.

---

## 3. 變更檔案清單

```text
新增:
  app/Consensus/Classifier/FailSafeQuestionClassifier.php
  app/Consensus/Extractor/JsonResponseExtractor.php
  app/Consensus/ResponseExtractionOrchestrator.php
  tests/Feature/Consensus/ResponseExtractionPersistenceTest.php
  tests/Unit/Consensus/Classifier/FailSafeBiasTest.php
  tests/Unit/Consensus/Extractor/JsonResponseExtractorTest.php

修改:
  app/Consensus/Contracts/ProviderResponseRepository.php
  app/Consensus/DTO/ProviderResponse.php
  app/Providers/ConsensusServiceProvider.php
  app/Repositories/EloquentProviderResponseRepository.php
  .ai-dev/orchestration/briefs/M4-A/progress.md
```

---

## 4. Worker 提交 / 5. Orchestrator 審核

| Worker 日期 | 2026-06-13 |
| **建議 Orchestrator 文件更新** | M4-A 放行後可更新 `gate-status.md`；無 README / docs 內容更新需求。 |
| 審核者 | Orchestrator |
| 日期 | 2026-06-13 |
| 結果 | ☑ **RELEASED** · ☐ REJECTED |
| **docs / README 整合** | ☑ N/A |
| Blocking | 無 |
| Non-blocking | `JsonResponseExtractor` 目前採 fixture JSON replay / local parser，不呼叫真 LLM extractor；後續若接真 extractor，應放在 `app/AI/` adapter 並維持 `ResponseExtractor` domain 介面。 |
| 備註 | 重跑 FailSafeBias 4 + JsonResponseExtractor 4 + ResponseExtractionPersistence 3 + 全 suite 30 passed；CT-G1–G3 通過。下一 Gate：**M4-B**。 |
