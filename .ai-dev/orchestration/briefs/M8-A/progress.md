# M8-A Progress — Product UX + Async + Email

| 欄位 | 值 |
|------|-----|
| Gate | **M8-A** |
| 狀態 | **RELEASED** |
| Brief | [brief.md](brief.md) |
| Spec | [docs/10-product-ux-and-async.md](../../../../docs/10-product-ux-and-async.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 實作摘要

| 項目 | 狀態 | 說明 |
|------|------|------|
| `processing_status` migration + backfill | ✅ | 新增欄位 `pending/running/completed/failed`；既有列 backfill `completed` |
| `RunAuthenticatedVerificationJob` | ✅ | 含 grounding + workflow + user_id + metadata；`failed()` 寫入 `processing_error` |
| `ConsensusWorkflow::run` optional `existingRequest` | ✅ | 加 `?VerificationRequest` 參數讓 Job 更新既有 pending 記錄；不改算法 |
| `POST /verifications` async | ✅ | 建 pending 記錄 → dispatch Job → redirect show |
| `GET /verifications/{id}/status` | ✅ | JSON polling endpoint |
| `GET /verifications` Index + 分頁 | ✅ | User 僅自己；Admin 全部 |
| `POST /verifications/{id}/replay` | ✅ | ConsensusReplayService + 設 completed |
| `verified` middleware on verification routes | ✅ | Demo 路由維持無 verified |
| `User` implements `MustVerifyEmail` | ✅ | |
| `Features::emailVerification()` | ✅ | Fortify config 啟用 |
| `VerifyEmail.vue` | ✅ | 繁中；resend + logout |
| 本機 auto-verify | ✅ | `APP_ENV=local/testing` 或 `AUTH_AUTO_VERIFY_EMAIL=true` 觸發 |
| `VerificationRequestPolicy` viewAny/replay | ✅ | user=本人；admin=全部 |
| `Demo/Index.vue` 分離 | ✅ | demo 改 render `Demo/Index`；`Verification/Index` 為 auth 列表 |
| `Verification/Index.vue` (auth 列表) | ✅ | 分頁 + 狀態 badge |
| `Verification/Show.vue` 更新 | ✅ | polling + pending/running/failed/completed UX + Replay 按鈕 |
| `AppLayout` nav 新增「我的驗證」 | ✅ | |
| Dashboard「查看全部」連結 | ✅ | |
| `M8AVerificationListTest` | ✅ | 7 tests |
| `M8AAsyncVerificationTest` | ✅ | 8 tests |
| `M8AEmailVerificationTest` | ✅ | 5 tests |
| `M7BVerificationAuthTest` 更新 | ✅ | 配合 async + verified 調整 |
| 全 suite 167 passed / 1 skipped | ✅ | |

---

## 2. 交付物對照

### 1. 資料庫
- [x] Migration：`verification_requests.processing_status`（`pending/running/completed/failed`）
- [x] 既有列 backfill `completed`

### 2. Job — `RunAuthenticatedVerificationJob`
- [x] 邏輯自 `AuthVerificationController::store` 抽出
- [x] 狀態轉換 + failed 時 `metadata.processing_error`
- [x] **MUST NOT** 改 `ConsensusWorkflow` 算法（僅加 optional `existingRequest` 參數）

### 3. HTTP
- [x] `POST /verifications` → pending → dispatch Job → redirect show
- [x] `GET /verifications/{id}/status` — JSON
- [x] `GET /verifications` — Index + pagination
- [x] `POST /verifications/{id}/replay`
- [x] Middleware `verified` on verification routes

### 4. Email Verification
- [x] `User` implements `MustVerifyEmail`
- [x] `Features::emailVerification()`
- [x] `VerifyEmail.vue`（繁中）
- [x] Local auto-verify：`AUTH_AUTO_VERIFY_EMAIL` / `APP_ENV=local`
- [x] Tests：unverified blocked；local verified allowed

### 5. 前端
- [x] `Pages/Verification/Index.vue` — 列表
- [x] `Pages/Verification/Show.vue` — polling/failed/completed/replay
- [x] `Pages/auth/VerifyEmail.vue`
- [x] `AppLayout` nav：「我的驗證」→ `/verifications`
- [x] Dashboard 連結列表

### 6. Policy
- [x] `VerificationRequestPolicy.viewAny` / `.replay`

### 7. Feature Tests
- [x] `M8AVerificationListTest.php` — 7 tests
- [x] `M8AAsyncVerificationTest.php` — 8 tests
- [x] `M8AEmailVerificationTest.php` — 5 tests
- [x] `M7BVerificationAuthTest` 更新（verified user + async）

---

## 3. 驗收

```text
php vendor/bin/pint --dirty          → passed
php artisan test --compact           → 167 passed, 1 skipped, 0 failed
php artisan test --filter=M8A        → 20 passed
```

---

## 4. Worker 提交

| Worker 日期 | 2026-06-14 |
|---|---|
| **建議 Orchestrator 文件更新** | `ConsensusWorkflow::run` 加入 `existingRequest` optional 參數（不改算法，僅改 persistence path）；`Demo/Index` 為 demo 頁元件（原 `Verification/Index`）；`Verification/Index` 現為 auth 列表頁 |

---

## 5. Orchestrator 審核

| 審核者 | Orchestrator |
| 結果 | ☑ **RELEASED** · ☐ REOPEN |
| 備註 | 167 passed / 1 skipped；typecheck 通過。`ConsensusWorkflow::run` optional `existingRequest` 為 persistence 擴充，符合 spec §3。Demo 頁分離至 `Demo/Index.vue` 已回寫 docs/10 §10.1。 |
