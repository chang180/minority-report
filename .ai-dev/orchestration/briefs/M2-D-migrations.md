# Worker Brief — Gate M2-D

**Milestone 2 · Audit migrations + Model skeleton**  
**前置 Gate**：M2-B **RELEASED**（可與 M2-C 並行，但 **M2-E 前必須 RELEASED**）  
**狀態**：BLOCKED

---

## 角色

Worker Agent。**只做 M2-D**：資料庫 migration 與 Eloquent model 骨架，對齊 audit trail。

---

## 必讀

1. [docs/02-contracts.md](../../../docs/02-contracts.md) §10 Audit Trail 欄位
2. [docs/07-milestones.md](../../../docs/07-milestones.md) M2 交付物
3. 本 brief

---

## 交付物

### 三張核心表（命名可調，欄位須覆蓋 audit 需求）

1. **`verification_requests`**
   - user question、classified type、classifier_confidence、answer_shape、requires_grounding、grounding_available
   - consensus result 摘要、final_trust、final_verdict（JSON/text）
   - timestamps

2. **`provider_responses`**（每 provider 一列）
   - verification_request_id、provider、model
   - provider_status、extraction_status
   - raw_answer（text）、normalized（JSON nullable）
   - error、metadata（JSON nullable）
   - usage（JSON nullable）

3. **`consensus_results`**（或合併進 requests；若分表須 FK）
   - alignment 結果、conflict detection、applied_caps、trust base 等 **JSON 欄位** 占位
   - 對應 02 §10 其餘 analyzer/scorer 產物

### Models

- `App\Models\VerificationRequest`
- `App\Models\ProviderResponse`
- `App\Models\ConsensusResult`（若使用）

Eloquent 即可；**Consensus domain MUST NOT 直接依賴 Eloquent**（01 §2.2）—— models 供 Laravel 層 persistence 用，M5 再接 repository。

### Migration

- 預設 SQLite 可跑：`php artisan migrate`
- foreign key、index 合理即可

---

## MUST NOT

- 實作 consensus 寫入邏輯（M5）
- 在 `app/Consensus/` 內 use Model
- 實作 UI（M6）

---

## 驗收

```bash
touch database/database.sqlite   # 若使用 sqlite 且檔案不存在
php artisan migrate --force
php artisan migrate:status
```

---

## 完成後交還

1. ER 簡圖或欄位對照 02 §10
2. migrate 輸出
3. 留給 M5：哪些 JSON 欄位待正式 schema 約束
