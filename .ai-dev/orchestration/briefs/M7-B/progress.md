# M7-B Progress — Provider + Admin Demo + Dashboard

| 欄位 | 值 |
|------|-----|
| Gate | **M7-B** |
| 狀態 | **RELEASED** |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 實作摘要

**完成日期**：2026-06-14

### 資料庫與 Models
- `verification_requests.user_id`（nullable FK）
- `users.consensus_slots`（json, nullable）
- `user_provider_settings`（unique user_id + provider_key；api_key encrypted）
- `user_custom_providers`（api_key encrypted）
- `system_demo_settings`（singleton；預設 fake_fixtures + demo_enabled=true；shared_api_key encrypted）
- `User` 新增 `providerSettings()` / `customProviders()` relations，`consensus_slots` cast

### Runtime
- `ScopedConfigLlmProvider`（`app/AI/Providers/`）：wraps LaravelAiLlmProvider，try-finally 還原 config，邏輯名覆蓋
- `ConfiguredLlmProviderFactory::forUser(User)` → 三 LlmProvider，邏輯名 openai/anthropic/gemini
- `ConfiguredLlmProviderFactory::forDemo(SystemDemoSettings)` → fake_fixtures 返回 disabled；shared_local_api 三槽指向 shared endpoint
- 未設定 / disabled / 無 key → `provider_unavailable`（LaravelAiLlmProvider disabled 行為）

### HTTP 與 Policy
- `VerificationRequestPolicy::view`：user 只能看自己的；admin 可看全部；demo 驗證（user_id=null）user 無法存取
- 路由：`/settings/providers`（GET+PUT preset/custom/slots），`/admin/demo`（GET+PUT），`/verifications/*`（create/store/show）
- `AuthVerificationController`：使用 `forUser()` providers，store 後更新 `user_id` + `metadata.source=authenticated`
- `AdminDemoController`：讀 `SystemDemoSettings::instance()`，admin only
- `DashboardController`：計算三槽就緒度 + 最近 verifications

### Demo 重構
- `VerificationController` 改讀 `SystemDemoSettings::instance()`
- `demo_enabled=false` → render `Demo/Closed`（繁中關閉頁）
- `store` → `demo_enabled=false` 時 abort 404
- 所有 demo verification 加 `metadata.source=demo` + `metadata.demo_mode`

### 前端（繁體中文）
- `Pages/settings/Providers.vue`：preset CRUD（key 不回傳原始值，只顯示 has_key）、自訂供應端、共識槽
- `Pages/admin/DemoSettings.vue`：mode 切換、fixture 管理、shared API 設定
- `Pages/Dashboard.vue`：三槽就緒度卡片、最近 verification 列表、總計、CTA
- `Pages/Verification/Create.vue`：登入版問題輸入（無 fixture）
- `Pages/Demo/Closed.vue`：demo 關閉友善提示
- `AppLayout.vue`：新增「新建驗證」「供應端設定」nav 連結；admin 可見「Demo 管理」
- 延續繁體中文 UI policy，無引入 vue-i18n

### Feature Tests（29 個，113 assertions）
- `M7BProviderSettingsTest`：10 個測試（CRUD、encrypted、no-raw-key、isolation）
- `M7BDemoAdminTest`：9 個測試（admin guard、demo_enabled、encrypted key、mode）
- `M7BVerificationAuthTest`：8 個測試（policy、user_id、metadata.source、min length）

---

## 2. 交付物對照

- [x] Migration: `verification_requests.user_id`
- [x] Migration: `users.consensus_slots`
- [x] Migration + Model: `user_provider_settings`（encrypted api_key）
- [x] Migration + Model: `user_custom_providers`（encrypted api_key）
- [x] Migration + Model: `system_demo_settings`（singleton；encrypted shared_api_key）
- [x] `User` relations + consensus_slots cast
- [x] `forUser(User)` / `forDemo(SystemDemoSettings)`
- [x] `ScopedConfigLlmProvider`（config override pattern）
- [x] `VerificationRequestPolicy`（user 自己 / admin 全部）
- [x] Routes: `/settings/providers`（GET+PUT）、`/admin/demo`（GET+PUT）、`/verifications/*`
- [x] `metadata.source = demo | authenticated`；MUST NOT 保存 api_key
- [x] Demo 重構讀 `system_demo_settings`；`demo_enabled=false` 顯示關閉頁
- [x] `Pages/settings/Providers.vue`
- [x] `Pages/admin/DemoSettings.vue`
- [x] `Pages/Dashboard.vue`（產品版）
- [x] `Pages/Verification/Create.vue`
- [x] `AppLayout` nav 更新
- [x] `M7BProviderSettingsTest` / `M7BDemoAdminTest` / `M7BVerificationAuthTest`

---

## 3. 驗收

```text
npm run typecheck      ✅ 無錯誤
vendor/bin/pint --dirty --format agent  ✅ 已修正
php artisan test --compact             ✅ 131 passed, 1 skipped
php artisan test --filter=M7B          ✅ 29 passed (113 assertions)
```

---

## 4. Worker 提交

| Worker 日期 | 2026-06-14 |
|-------------|------------|
| **建議 Orchestrator 文件更新** | |

1. **`.env.example`** — 新增 `ADMIN_EMAIL` / `ADMIN_PASSWORD` 已有，無新 key 需要
2. **`README.md` 路由表** — 新增 `/settings/providers`、`/admin/demo`、`/verifications/create`、`/verifications/{id}`
3. **`docs/07-milestones.md §M7-B`** — 更新狀態為 RELEASED
4. **`docs/08-ui-auth-providers.md §7`** — M7-B 驗收 checklist 勾選

---

## 5. Orchestrator 審核

| 審核者 | Orchestrator |
| 結果 | ☑ **RELEASED** |
| 驗收 | typecheck ✓ · pint ✓ · 131 tests passed · M7B 29 passed |
| 備註 | BYOK provider、admin demo、Dashboard、auth verification；README / 07 / 08 §7 已整合 |
