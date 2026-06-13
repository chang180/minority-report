# M2-D Progress — Audit migrations + Models

| 欄位 | 值 |
|------|-----|
| Gate | **M2-D** |
| 狀態 | **RELEASED** |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 交付物檢核

### 1.1 Migrations

- [x] `2026_06_13_061123_create_verification_requests_table.php`
- [x] `2026_06_13_061124_create_provider_responses_table.php`
- [x] `2026_06_13_061125_create_consensus_results_table.php`

### 1.2 `verification_requests` 欄位（對照 02 §10）

- [x] user question（text）
- [x] classified type、classifier_confidence、answer_shape
- [x] requires_grounding、grounding_available
- [x] consensus / final_trust / final_verdict（JSON 或 text）
- [x] timestamps

### 1.3 `provider_responses` 欄位

- [x] verification_request_id（FK）
- [x] provider、model
- [x] provider_status、extraction_status
- [x] raw_answer、normalized（JSON）
- [x] error、metadata、usage（JSON nullable）

### 1.4 `consensus_results` 欄位（若分表）

- [x] verification_request_id（FK）
- [x] alignment / conflict / caps / trust JSON 占位

### 1.5 Models

- [x] `App\Models\VerificationRequest`
- [x] `App\Models\ProviderResponse`
- [x] `App\Models\ConsensusResult`（若使用）

### 1.6 禁止項

- [x] `app/Consensus/` **內**無 Eloquent `use`
- [x] **無** consensus 寫入邏輯（M5）

---

## 2. 驗收命令

```bash
touch database/database.sqlite
php artisan migrate --force
php artisan migrate:status
```

### 2.1 輸出紀錄

```text
$ touch database/database.sqlite
SQLite database existed: database/database.sqlite

$ php artisan migrate --force
INFO  Running migrations.
2026_06_13_061123_create_verification_requests_table ... DONE
2026_06_13_061124_create_provider_responses_table ...... DONE
2026_06_13_061125_create_consensus_results_table ....... DONE

$ php artisan migrate:status
Migration name ......................................... Batch / Status
0001_01_01_000000_create_users_table .................. [1] Ran
0001_01_01_000001_create_cache_table .................. [1] Ran
0001_01_01_000002_create_jobs_table ................... [1] Ran
2026_06_13_054839_create_agent_conversations_table .... [2] Ran
2026_06_13_061123_create_verification_requests_table .. [3] Ran
2026_06_13_061124_create_provider_responses_table ..... [3] Ran
2026_06_13_061125_create_consensus_results_table ...... [3] Ran

$ vendor/bin/pint --dirty --format agent
{"tool":"pint","result":"passed"}

$ php artisan test --compact
Tests: 2 passed (11 assertions)
```

---

## 3. 02 §10 對照表

```text
verification_requests.question
  -> user question

verification_requests.classified_type
verification_requests.classifier_confidence
verification_requests.answer_shape
verification_requests.requires_grounding
  -> classified type / classifier confidence / answer_shape / requires_grounding

verification_requests.grounding_available
  -> grounding_available（MVP runtime 固定 false 的持久化欄位）

provider_responses.provider_prompt
provider_responses.provider
provider_responses.model
provider_responses.raw_answer
provider_responses.provider_status
provider_responses.error
provider_responses.metadata
provider_responses.created_at / updated_at
  -> provider prompts / raw provider responses / provider_status / errors / timestamps

provider_responses.extraction_prompt
provider_responses.extractor_model
provider_responses.extraction_status
provider_responses.normalized
provider_responses.usage
  -> extraction prompt / extractor model / extraction_status / normalized responses / usage

provider_responses.normalized->claims
  -> claims 與 canonical_key（normalized JSON 內保存）

consensus_results.alignment
  -> claim alignment 結果（aligned / unmatched / unalignable）

consensus_results.conflict_detection
  -> conflict detection 結果

consensus_results.consensus
verification_requests.consensus_summary
  -> consensus result / request 層摘要

consensus_results.decision_key
consensus_results.decision_basis
  -> 判定走 discrete 或 open 主鍵

consensus_results.trust_base
consensus_results.applied_caps
consensus_results.trust_level
verification_requests.final_trust
  -> trust level base / 套用過的 caps / final trust level

consensus_results.verdict_report
verification_requests.final_verdict
  -> final verdict / reporter narrative

verification_requests.errors
provider_responses.error
consensus_results.errors
  -> errors（各 stage）

*_created_at / *_updated_at
  -> timestamps（各 stage）
```

---

## 4. Worker 提交

| Worker 日期 | 2026-06-13 |
| **建議 Orchestrator 文件更新** | N/A；本 Gate 未改 `docs/` 或根 `README.md`。 |

---

## 5. Orchestrator 審核

| 項目 | 內容 |
|------|------|
| 審核者 | Orchestrator |
| 日期 | 2026-06-13 |
| 結果 | ☑ **RELEASED** · ☐ REJECTED |
| **docs / README 整合** | ☑ N/A |
| Blocking | 無 |
| 備註 | 重跑：`migrate --force` OK（3 張 audit 表 batch 3）；`app/Consensus/` 無 Eloquent；models 關聯與 02 §10 對照完整。M2 僅剩 **M2-E**。下一 Gate：**M2-E**。 |
