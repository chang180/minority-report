# M2-D Progress — Audit migrations + Models

| 欄位 | 值 |
|------|-----|
| Gate | **M2-D** |
| 狀態 | **OPEN** |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 交付物檢核

### 1.1 Migrations

- [ ] `*create_verification_requests_table.php`
- [ ] `*create_provider_responses_table.php`
- [ ] `*create_consensus_results_table.php`（或合併設計已文件化）

### 1.2 `verification_requests` 欄位（對照 02 §10）

- [ ] user question（text）
- [ ] classified type、classifier_confidence、answer_shape
- [ ] requires_grounding、grounding_available
- [ ] consensus / final_trust / final_verdict（JSON 或 text）
- [ ] timestamps

### 1.3 `provider_responses` 欄位

- [ ] verification_request_id（FK）
- [ ] provider、model
- [ ] provider_status、extraction_status
- [ ] raw_answer、normalized（JSON）
- [ ] error、metadata、usage（JSON nullable）

### 1.4 `consensus_results` 欄位（若分表）

- [ ] verification_request_id（FK）
- [ ] alignment / conflict / caps / trust JSON 占位

### 1.5 Models

- [ ] `App\Models\VerificationRequest`
- [ ] `App\Models\ProviderResponse`
- [ ] `App\Models\ConsensusResult`（若使用）

### 1.6 禁止項

- [ ] `app/Consensus/` **內**無 Eloquent `use`
- [ ] **無** consensus 寫入邏輯（M5）

---

## 2. 驗收命令

```bash
touch database/database.sqlite
php artisan migrate --force
php artisan migrate:status
```

### 2.1 輸出紀錄

```text
（Worker 貼上 migrate:status）
```

---

## 3. 02 §10 對照表

```text
（Worker 填：DB 欄位 → audit trail 欄位映射）
```

---

## 4. Worker 提交 / 5. Orchestrator 審核

| Worker 日期 | |
| **建議 Orchestrator 文件更新** | |
| Orchestrator 結果 | ☐ RELEASED · ☐ REJECTED |
| **docs / README 整合** | ☐ 已更新 · ☐ N/A |
