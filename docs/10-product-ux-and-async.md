# 10 — 產品 UX 與非同步 Verification（Post-MVP · Milestone 8-A）

本文件定義 Milestone 8-A 的 **Verification 列表**、**非同步 Job**、**Email verification**、**Replay UI** 與處理狀態 UX。  
M1–M7 與 M8-B consensus / grounding domain **MUST NOT** 因 M8-A 而改寫 Cases 1–6 或 Grounding 策略。

**前置**：M7 **RELEASED** · M8-B **RELEASED**。M8-A Worker **MUST** 先讀本文件、[07-milestones.md §M8-A](07-milestones.md)、Gate brief。

---

## 1. 範圍與邊界

### 1.1 M8-A 要做

| 領域 | 說明 |
|------|------|
| `processing_status` | `verification_requests` 新增處理狀態欄位 |
| Async Job | `POST /verifications` 建立 pending 紀錄後 dispatch Job |
| 列表 | `GET /verifications` — 本人分頁列表；admin 可檢視全部 |
| Polling | `GET /verifications/{id}/status` JSON；Show 頁輪詢 |
| Email verification | Fortify 啟用；本機 auto-verify；生產須完成驗證信（D1） |
| Replay | `POST /verifications/{id}/replay` + Show UI 按鈕 |
| 測試 | `M8AVerificationListTest`、`M8AAsyncVerificationTest`、`M8AEmailVerificationTest` |

### 1.2 M8-A MUST NOT

- 改寫 [03-consensus-algorithm.md](03-consensus-algorithm.md) Cases 1–6
- 改寫 M8-B Grounding / Trust cap（`app/Grounding/`、`CascadeTrustLevelScorer` 邏輯）
- M8-C semantic aligner
- 破壞 Demo 訪客路由（`/demo/*` **MAY** 維持同步 fake fixture 路徑）
- 將 API key 寫入 audit / log / Inertia props

### 1.3 與 M8-B 的關係

- M8-B 已將 **grounding fetch + workflow** 整合於 `AuthVerificationController::store`（同步）
- M8-A **MUST** 將相同邏輯 **移入** `RunAuthenticatedVerificationJob`，**MUST NOT** 刪除或簡化 grounding 步驟
- Job 內仍使用 `GroundingService` + `ConfiguredLlmProviderFactory::forUser()` + `ConsensusWorkflow::run()`

---

## 2. `processing_status`

### 2.1 欄位

| 欄位 | 型別 | 說明 |
|------|------|------|
| `processing_status` | string | `pending` \| `running` \| `completed` \| `failed` |

**Migration**：

- 新增欄位，預設 `pending`
- 既有列 **MUST** backfill 為 `completed`（歷史紀錄皆已同步跑完）

### 2.2 狀態轉換

```text
POST /verifications
    → create record (processing_status = pending)
    → dispatch RunAuthenticatedVerificationJob
    → Job start: running
    → workflow success: completed
    → exception / unrecoverable error: failed (+ metadata.processing_error)
```

| 狀態 | UI 繁中 | 說明 |
|------|---------|------|
| `pending` | 等待處理 | 已建立、Job 尚未開始 |
| `running` | 分析中 | Job 執行 grounding + workflow |
| `completed` | 已完成 | 可顯示完整 consensus 結果 |
| `failed` | 處理失敗 | 顯示 `metadata.processing_error`（若有） |

`failed` 時 **MAY** 保留部分欄位為 null；Show **MUST** 顯示失敗訊息而非空白頁。

---

## 3. `RunAuthenticatedVerificationJob`

### 3.1 職責

自現有 `AuthVerificationController::store` 抽出，**MUST** 包含：

1. 載入 `VerificationRequest` 與 `user_id`
2. `ConfiguredLlmProviderFactory::forUser($user)`
3. `GroundingService::fetch()` + metadata / `providerPrompt` 組裝（同 M8-B）
4. `ConsensusWorkflow::run()`
5. 更新 `user_id`、`metadata.source = authenticated`
6. 設定 `processing_status`

### 3.2 失敗處理

- 未捕獲例外 → `processing_status = failed`
- `metadata.processing_error` **SHOULD** 含簡短訊息（**MUST NOT** 含 API key）
- Job **SHOULD** 使用 `$tries` / timeout 合理值（Worker 依 queue 設定）

### 3.3 Queue

- 預設 `QUEUE_CONNECTION=database`（見 `.env.example`）
- 測試 **MUST** 使用 `Queue::fake()` 或 `Bus::fake()` 或 sync driver 驗證 dispatch

---

## 4. HTTP 路由

### 4.1 路由表（新增 / 變更）

| 方法 | 路徑 | Middleware | 說明 |
|------|------|------------|------|
| `GET` | `/verifications` | `auth`, `verified` | 列表（分頁） |
| `GET` | `/verifications/create` | `auth`, `verified` | 新建（既有） |
| `POST` | `/verifications` | `auth`, `verified` | 建立 pending → dispatch Job → redirect show |
| `GET` | `/verifications/{id}` | `auth`, `verified` | 結果頁（polling UX） |
| `GET` | `/verifications/{id}/status` | `auth`, `verified` | JSON status（見 §4.3） |
| `POST` | `/verifications/{id}/replay` | `auth`, `verified` | Replay（§7） |

**Demo 路由**（`/demo/*`）：**MUST NOT** 加 `verified`；**MAY** 維持同步。

### 4.2 `POST /verifications` 行為變更

**M7-B（舊）**：同步跑完整 workflow 後 redirect。  
**M8-A（新）**：

1. Validate `question`
2. Create `VerificationRequest`：`question` + `user_id` + `processing_status=pending` + `metadata.source=authenticated`
3. Dispatch `RunAuthenticatedVerificationJob`
4. Redirect `GET /verifications/{id}`

### 4.3 `GET /verifications/{id}/status`

**Response JSON**（200）：

```json
{
  "id": 1,
  "processing_status": "running",
  "processing_error": null,
  "final_trust": null,
  "final_verdict": null,
  "updated_at": "2026-06-14T12:00:00.000000Z"
}
```

- `processing_error` 取自 `metadata.processing_error`（failed 時）
- `completed` 時 **MAY** 含 `final_trust`、`final_verdict`
- Policy：同 `view`

Show 頁 **SHOULD** 每 2–3 秒 poll 直至 `completed` 或 `failed`，完成後 reload 或 merge props。

### 4.4 `GET /verifications`

- **User**：`where user_id = auth id`，`latest()`，分頁（預設 15）
- **Admin**：**MAY** 檢視全部（policy `viewAny`）
- 每列 **SHOULD** 含：`id`, `question`（截斷）, `processing_status`, `final_trust`, `created_at`

---

## 5. Email Verification（D1）

### 5.1 Fortify

| 項目 | 規格 |
|------|------|
| `User` | **MUST** implement `Illuminate\Contracts\Auth\MustVerifyEmail` |
| `config/fortify.php` | **MUST** 啟用 `Features::emailVerification()` |
| 頁面 | `VerifyEmail.vue`（繁中）；Fortify 預設路由 |

### 5.2 本機 auto-verify

**MUST** 支援以下任一（Worker 擇一或組合）：

| 機制 | 說明 |
|------|------|
| `AUTH_AUTO_VERIFY_EMAIL=true` | 註冊 / 登入時自動 `markEmailAsVerified()` |
| `APP_ENV=local` | 等同 auto-verify（**MAY** 與上列合併） |
| `MAIL_MAILER=log` listener | Registered 事件後自動 verified（測試友好） |

`.env.example` **MUST** 文件化 `AUTH_AUTO_VERIFY_EMAIL`。

### 5.3 生產

- 使用者 **MUST** 完成 Email 驗證後才能存取 §4 的 verification 路由（`verified` middleware）
- Dashboard、settings **MAY** 維持僅 `auth`（Worker **SHOULD** 與 kit 一致：verification 相關加 `verified`）

### 5.4 測試

- 未驗證 user → `POST /verifications` **403** 或 redirect verify-email
- 本機 auto-verify → 可正常建立 verification

---

## 6. 前端（繁體中文）

### 6.1 新增 / 更新頁面

| 頁面 | 路徑 | 說明 |
|------|------|------|
| `Verification/Index.vue` | `/verifications` | 列表 + 分頁 + 狀態 badge |
| `Verification/Show.vue` | `/verifications/{id}` | pending/running 輪詢；failed 錯誤；completed 既有結果 + Replay |
| `auth/VerifyEmail.vue` | Fortify | 驗證信說明 + resend |

### 6.2 導覽

- `AppLayout` **MUST** 新增「我的驗證」→ `/verifications`
- Dashboard **SHOULD** 連結至完整列表

### 6.3 狀態 UX

| `processing_status` | 顯示 |
|---------------------|------|
| `pending` | 「等待處理…」+ spinner |
| `running` | 「分析中…」+ spinner |
| `completed` | 既有 consensus / trust / minority UI；`final_verdict` 為**繁體中文**多行文字（見 [03 §10](03-consensus-algorithm.md)、[05 §10](05-failure-modes.md)） |
| `failed` | 「處理失敗」+ 錯誤摘要 |

---

## 7. Replay

### 7.1 行為

- `POST /verifications/{id}/replay` 呼叫 `ConsensusReplayService::replayRequest($id)`
- 建立 **新** `VerificationRequest`（metadata 含 `replay.source_request_id`）
- Redirect 至新紀錄的 Show

### 7.2 授權

- `user`：**MUST** 僅能 replay **自己的** verification
- `admin`：**MAY** replay 任意 verification
- Policy **MUST** 新增 `replay` ability

### 7.3 UI

- Show 頁（`completed`）**SHOULD** 顯示「重新分析」按鈕
- Replay 產生的新紀錄 **MAY** 走同步 replay workflow（**無需** 再 dispatch 同一 Job 模式；`replayFromPersisted` 為既有 domain 行為）

---

## 8. Policy 擴充

`VerificationRequestPolicy` **MUST** 新增：

| Ability | User | Admin |
|---------|------|-------|
| `viewAny` | 僅自己的列表 scope | 全部 |
| `view` | 本人 | ✓ |
| `replay` | 本人 | ✓ |

---

## 9. 測試

### 9.1 `M8AVerificationListTest.php`

- 登入 user 僅見自己的列表
- admin 可見全部（若實作）
- 分頁 / 排序

### 9.2 `M8AAsyncVerificationTest.php`

- `POST /verifications` dispatch Job
- Job 成功 → `completed` + consensus 存在
- Job 失敗 → `failed` + `processing_error`
- `GET .../status` JSON 正確

### 9.3 `M8AEmailVerificationTest.php`

- 未驗證 blocked
- auto-verify 路徑通過

### 9.4 回歸

- 更新 `M7BVerificationAuthTest` 等：使用 verified user + async 斷言
- 全 suite 綠；`npm run typecheck` 通過

---

## 10. Milestone 8-A 驗收

- [x] Migration `processing_status` + backfill
- [x] `RunAuthenticatedVerificationJob`（含 M8-B grounding 邏輯）
- [x] Async `POST /verifications` + status JSON + Show polling
- [x] `GET /verifications` Index + nav
- [x] Fortify email verification + 本機 auto-verify
- [x] `verified` middleware on verification routes
- [x] Replay route + UI + policy
- [x] `M8A*` tests；全 suite 綠（167 passed）
- [x] **MUST NOT** 改 Grounding / Trust / Cases

### 10.1 實作備註（RELEASED 2026-06-14）

- `ConsensusWorkflow::run()` 新增 optional `?VerificationRequest $existingRequest`，供 async Job 更新既有 pending 列（**不改** Cases 1–6 算法）
- Demo 頁改 render `Demo/Index.vue`；`Verification/Index.vue` 為登入使用者列表

---

## Traceability

| 本文件章節 | 對應 |
|------------|------|
| §1 範圍 | [07-milestones.md §M8-A](07-milestones.md)、[m8-roadmap.md §M8-A](../.ai-dev/planning/m8-roadmap.md) |
| §2–3 Async | User D3、`.env` `QUEUE_CONNECTION` |
| §5 Email | User D1、[08-ui-auth-providers.md §2.4](08-ui-auth-providers.md) |
| §7 Replay | `ConsensusReplayService`、M5 audit |
| §8 Policy | [08-ui-auth-providers.md §6](08-ui-auth-providers.md) |
| §9–10 測試 | M8-A Worker brief |

**技術決策**：Verification 走 **database queue + polling**；Email 本機 **auto-verify**；生產 **must verify**。
