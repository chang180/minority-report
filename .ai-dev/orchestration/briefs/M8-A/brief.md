# Worker Brief — Gate M8-A

**Milestone 8 · Verification 列表 + 非同步 Job + Email Verification + Replay**  
**前置**：M7 **RELEASED** · M8-B **RELEASED**  
**狀態**：**可開工**（2026-06-14）

> M8-A 在 M8-B **之後**開工（User 優先 Grounding）。**MUST NOT** 重做 Grounding 或改 Trust 算法。

---

## 角色

Worker Agent。**只做 M8-A**：列表、async、email verify、replay UI、狀態 UX。

---

## 必讀

1. **[docs/10-product-ux-and-async.md](../../../../docs/10-product-ux-and-async.md)**（全文）
2. [docs/08-ui-auth-providers.md §2.4](../../../../docs/08-ui-auth-providers.md)（Fortify features）
3. 現有：`AuthVerificationController`、`DashboardController`、`ConsensusReplayService`、`FortifyServiceProvider`
4. 本 brief · [progress.md](progress.md)

---

## 背景

| 已有 | 缺口 |
|------|------|
| `POST /verifications` 同步跑完整 workflow | 易 timeout；需 Job |
| Dashboard 最近 5 筆 | 無 `/verifications` 列表 |
| Fortify 無 email verification | D1：本機 auto-verify / 生產需驗證 |
| `ConsensusReplayService` | 無 UI 入口 |
| M8-B Grounding 已整合於 store | **保留**；移入 Job |

---

## 交付物

### 1. 資料庫

- [ ] Migration：`verification_requests.processing_status`（`pending`/`running`/`completed`/`failed`）
- [ ] 既有列 backfill `completed`

### 2. Job — `RunAuthenticatedVerificationJob`

- [ ] 邏輯自現有 `AuthVerificationController::store` 抽出（grounding + workflow + user_id + metadata）
- [ ] 狀態轉換 + failed 時 `metadata.processing_error`
- [ ] **MUST NOT** 改 `ConsensusWorkflow` 算法

### 3. HTTP

- [ ] `POST /verifications` → create pending record → dispatch Job → redirect show
- [ ] `GET /verifications/{id}/status` — JSON for polling
- [ ] `GET /verifications` — Index controller + pagination
- [ ] `POST /verifications/{id}/replay` — `ConsensusReplayService::replayRequest`
- [ ] Middleware `verified` on verification routes（§4 spec）

### 4. Email Verification（D1）

- [ ] `User` implements `MustVerifyEmail`
- [ ] `Features::emailVerification()` in fortify config
- [ ] `VerifyEmail.vue`（繁中）+ Fortify views
- [ ] Local auto-verify：`AUTH_AUTO_VERIFY_EMAIL` 或 `APP_ENV=local` / `MAIL_MAILER=log` listener
- [ ] Tests：unverified blocked；local verified allowed

### 5. 前端（繁體中文）

- [ ] `Pages/Verification/Index.vue` — 列表
- [ ] `Pages/Verification/Show.vue` — polling UI、failed 狀態、Replay 按鈕
- [ ] `Pages/auth/VerifyEmail.vue`
- [ ] `AppLayout` nav：「我的驗證」→ `/verifications`
- [ ] Dashboard 連結列表

### 6. Policy

- [ ] `VerificationRequestPolicy` — `viewAny` / `replay`（admin 規則見 spec §8）

### 7. Feature Tests

- [ ] `M8AVerificationListTest.php`
- [ ] `M8AAsyncVerificationTest.php`
- [ ] `M8AEmailVerificationTest.php`
- [ ] 更新 `M7BVerificationAuthTest` 等必要處（verified user + async）

---

## 驗收命令

```text
npm run typecheck
vendor/bin/pint --dirty --format agent
php artisan test --compact
php artisan test --filter=M8A
php artisan queue:work --once   # 手動 smoke（可選）
```

---

## MUST NOT

- 改 Grounding / Trust / Consensus Cases（M8-B 已交付）
- M8-C semantic aligner
- 修改 `docs/`、根 `README.md`
- Demo 路由 **必須** 保持可用（可維持同步 fake fixture）

---

## 完成後交還

1. 更新 [progress.md](progress.md) §1–4
2. §4 列「建議 Orchestrator 文件更新」
3. 使用者轉交 Orchestrator **審核 RELEASED**

---

## 建議實作順序

```text
1. processing_status migration + Job 抽出 store 邏輯
2. Async POST + status JSON + Show polling
3. Email verification + verified middleware
4. Index 列表 + nav
5. Replay route + UI
6. Tests + full suite
```
