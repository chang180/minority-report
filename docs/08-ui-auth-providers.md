# 08 — UI、Auth 與 Provider 憑證（Post-MVP · Milestone 7）

本文件定義 Milestone 7 的**產品級 UI、身份驗證、per-user provider 憑證與訪客 demo 管理**規格。  
M1–M6 的 consensus domain 契約 **MUST NOT** 因 M7 而改寫算法；M7 僅擴充 **應用層**（HTTP、Auth、設定持久化、Inertia 頁面）。

**前置**：M6 Minimal UI **RELEASED**。M7 Worker **MUST** 先讀本文件與 [07-milestones.md §M7](07-milestones.md)。

---

## 1. 範圍與邊界

### 1.1 M7 要做

| 領域 | 說明 |
|------|------|
| Auth | Session 登入、**開放註冊**、密碼重設；角色 `admin` \| `user` |
| Auth 技術 | **Laravel Fortify**（Laravel 13 官方 starter kit 同款）；**MUST NOT** 使用 Breeze |
| 前端骨架 | 自 [laravel/vue-starter-kit](https://github.com/laravel/vue-starter-kit) 移植 layout / auth / settings；Inertia Vue 3 + TypeScript + Tailwind 4 |
| 公開頁 | Welcome、訪客 Demo |
| 登入後 | Dashboard、Provider 設定、驗證流程（真 provider） |
| Admin | 訪客 Demo 模式管理 |
| Provider | SDK preset + 使用者自訂 endpoint；encrypted 儲存 |

### 1.2 M7 MUST NOT

- 改寫 [03-consensus-algorithm.md](03-consensus-algorithm.md) 判定邏輯
- 在 `app/Consensus/` 讀取 Auth、User、provider 憑證 DB
- 將 API key / token 寫入 audit metadata 或 `verification_requests` 可公開欄位
- 引入 Phase 3 grounding、RAG、語意對齊
- 多租戶、團隊協作、付費

### 1.3 與 MVP Non Goals 的關係

[00-product-vision.md §5](00-product-vision.md) MVP 排除「多使用者團隊協作」。M7 的「多使用者」指 **個人帳號 + 各自 provider 設定**，**不是** 組織/團隊共享 workspace。

### 1.4 Starter Kit 採用策略（選擇性移植，非整包安裝）

本專案 **MUST NOT** 以 `laravel new --vue` 重建專案，也 **MUST NOT** 將 vue-starter-kit **整包覆蓋** 到現有 codebase。

**採用方式**：在既有 `minority-report` repo 上 **選擇性移植** kit 中與 M7 spec 對應的檔案（Fortify、layouts、auth/settings 頁、必要 shadcn-vue 元件與 npm 依賴）。

**理由**：

1. **避免保留不需要的 scaffold**：整包安裝會連帶預設 Welcome、範例 Dashboard、與本專案無關的 CRUD/範例路由與頁面；事後容易遺漏清理，與 Verification / Demo / Consensus 產品 IA 衝突。
2. **貼合既有 domain 投資**：M1–M6 的 `app/Consensus/`、audit models、fake fixtures、M6 Verification 頁與 Feature tests **MUST** 保留並接線，而非被 kit 預設結構取代。
3. **控制前端增量**：只引入本 Gate 需要的 layout/auth 骨架；Welcome、Demo、Dashboard、Provider 設定依 [§3](08-ui-auth-providers.md) 與本專案 spec **客製**，而非沿用 kit 預設文案與 IA。
4. **降低 merge 風險**：維持 `resources/js/Pages/`（大寫 P）、現有 `vite`/`pest` 設定，只追加 kit 缺的目錄（如 `layouts/`、`components/ui/`）。

**Worker 流程（M7-A）**：

```text
1. composer require laravel/fortify → 移植 Fortify Actions / ServiceProvider
2. 對照 vue-starter-kit，逐項移植 layouts、auth 頁、必要 ui 元件
3. 明確列出「不移植」清單（kit 預設 dashboard 內容、範例 CRUD、多餘 routes）
4. 接線本專案：Welcome、/demo、HandleInertiaRequests、admin role
5. 刪除孤立 scaffold（如 M2 Welcome.vue）
```

**對齊官方**：auth **行為** 與 Laravel 13 starter kit 一致（Fortify）；**頁面與 IA** 以本文件為準，**MAY** 在視覺上參考 kit，**MUST NOT** 未經 spec 整包照搬 kit 應用結構。

---

## 2. 身份驗證與授權

### 2.1 技術選型

| 項目 | 規格 |
|------|------|
| Guard | `web` session（`config/auth.php`） |
| Auth 套件 | **`laravel/fortify`** |
| 前端 | Inertia 頁（Login、Register、Forgot/Reset Password、Profile、Password） |
| 參考實作 | Laravel 13 [Starter Kits — Authentication](https://laravel.com/docs/13.x/starter-kits#authentication)、vue-starter-kit |

### 2.2 使用者模型

`users` 表 **MUST** 包含：

| 欄位 | 型別 | 說明 |
|------|------|------|
| `id` | bigint | PK |
| `name` | string | |
| `email` | string | unique |
| `password` | string | hashed |
| `role` | string | `user`（預設）\| `admin` |
| `email_verified_at` | timestamp | nullable；email verification **MAY** 於 M7-B 啟用 |
| `consensus_slots` | json | nullable；M7-B：三槽 provider 對應（見 §5.3） |

`User::isAdmin(): bool` **MUST** 存在。

### 2.3 角色與授權

| 角色 | 權限 |
|------|------|
| `guest` | Welcome、Demo、auth 頁 |
| `user` | Dashboard、自己的 verification、Provider 設定、Profile |
| `admin` | 上述 + Admin Demo 設定；**MAY** 檢視所有 verification（policy 可選） |

Middleware `admin` **MUST** 保護 `/admin/*`。

初始 admin **MUST** 可由 seeder + `ADMIN_EMAIL` / `ADMIN_PASSWORD` 建立。

### 2.4 Fortify 功能（M7-A / M7-B）

| Feature | M7-A | M7-B |
|---------|------|------|
| Registration | **MUST** 啟用 | — |
| Login / Logout | **MUST** | — |
| Password Reset | **MUST** | — |
| Profile / Password update | **MUST**（kit settings 頁） | — |
| Email Verification | **MAY** 關閉 | **MAY** 啟用 |
| Two-Factor Authentication | **MUST NOT** 啟用（M7-A） | **MAY** 啟用 |

---

## 3. 前端架構

### 3.1 技術棧（延續並擴充 [01-architecture.md §1](01-architecture.md)）

| 項目 | 規格 |
|------|------|
| Inertia | `@inertiajs/vue3` |
| Vue | 3 Composition API + TypeScript |
| CSS | Tailwind CSS 4 |
| 元件 | shadcn-vue（自 vue-starter-kit 移植） |
| 頁面目錄 | **`resources/js/Pages/`**（大寫 `P`；**MUST NOT** 改小寫，避免 Linux CI case-sensitive 回歸） |
| 共用 | `resources/js/layouts/`、`resources/js/components/`、`resources/js/lib/` |

### 3.2 Layout

| Layout | 用途 |
|--------|------|
| `GuestLayout` | Welcome、Demo、未登入 auth 頁 |
| `AppLayout` | 登入後主應用（sidebar 或 header，依 kit） |
| `AdminLayout` | Admin 子區（**MAY** 嵌於 AppLayout） |

`HandleInertiaRequests` **MUST** 共享 `auth.user`（含 `id`, `name`, `email`, `role`）。

### 3.3 頁面清單

| 頁面 | 路由 | Gate | 說明 |
|------|------|------|------|
| Welcome | `GET /` | M7-A | 產品介紹；CTA → Demo / Register |
| Demo Index | `GET /demo` | M7-A | 訪客問題輸入 + fixture 選擇 |
| Demo Show | `GET /demo/verifications/{id}` | M7-A | 訪客結果（reuse 結果元件） |
| Login / Register / … | Fortify | M7-A | kit auth 頁 |
| Dashboard | `GET /dashboard` | M7-A 殼 / M7-B 內容 | 登入後首頁 |
| Verification Create | `GET /verifications/create` | M7-B | 無 fixture；用使用者 provider |
| Verification Show | `GET /verifications/{id}` | M7-B | auth；policy 限制本人 |
| Provider Settings | `GET/PUT /settings/providers` | M7-B | SDK preset + 自訂 endpoint |
| Admin Demo Settings | `GET/PUT /admin/demo` | M7-B | 訪客 demo 模式 |
| Profile / Password | kit 預設 | M7-A | `/settings/profile` 等 |

`GET /health` **MUST** 保留。

### 3.4 顯示語言（繁體中文）

本產品 UI **ONLY** 使用**繁體中文**作為使用者可見文案語言。

| 類別 | 語言 | 範例 |
|------|------|------|
| 按鈕、標題、nav、表單 label、說明、Dashboard | **繁體中文** | 「登入」「註冊」「設定」「儀表板」 |
| Validation / 錯誤 / flash（使用者可見） | **繁體中文** | Form Request、`lang/zh_TW` |
| API / domain / audit 參數 | **英文** | `openai`、`Full`、`provider_unavailable`、fixture id |
| Model 名、JSON key、路由 path | **英文** | `gemma-4-…`、`consensus_status` |
| 程式識別符、HTML `autocomplete` | 不強制中文化 | — |

規則：

- M7 **MUST NOT** 引入 vue-i18n 等多語系框架；硬編碼繁中即可。
- M7-A **MUST** 將 kit 移植頁（auth、settings、layout、Dashboard）及 M6 Verification 頁改為繁中。
- M7-B **MUST** 延續同一語言政策。
- `APP_LOCALE` **SHOULD** 為 `zh_TW`（Orchestrator 整合 `.env.example` / README）。

### 3.5 M6 路由遷移

M6 的 `/` demo **MUST** 遷至 `/demo/*`（M7-A）。  
既有 `M6MinimalUiTest` **MUST** 更新為 `/demo` prefix。

---

## 4. 訪客 Demo（Admin 可管理）

### 4.1 設定模型 `system_demo_settings`

Singleton（一行或 key-value）。欄位：

| 欄位 | 型別 | 說明 |
|------|------|------|
| `mode` | string | `fake_fixtures` \| `shared_local_api` |
| `demo_enabled` | boolean | 是否開放 `/demo` |
| `shared_api_url` | string | nullable；`shared_local_api` 時必填 |
| `shared_api_key` | string | nullable；encrypted |
| `default_fixture_id` | string | fake 模式預選 fixture |
| `enabled_fixture_ids` | json | fake 模式可選 fixture 列表 |

### 4.2 行為

| `mode` | Provider 來源 | 說明 |
|--------|---------------|------|
| `fake_fixtures` | `ConsensusDemoFixtureCatalog` | 同 M6；不需 API key |
| `shared_local_api` | Admin 設定的 shared url/key | 三 consensus 槽皆指向同一 endpoint（如 Ollama） |

訪客 verification 的 `verification_requests.user_id` **MUST** 為 `null`。

僅 `admin` **MAY** 更新 demo settings。

---

## 5. Per-User Provider 憑證

### 5.1 原則

- Domain `ConsensusWorkflow::run($question, LlmProvider[] $providers)` **MUST** 維持注入式；憑證解析在 **`app/AI/` + HTTP 層**。
- 全域 `.env` key（`config/ai.php`）**MAY** 保留作 fallback / CI；登入使用者驗證 **SHOULD** 優先使用 per-user 設定。
- API key **MUST** 以 Laravel `encrypted` cast 儲存；**MUST NOT** 出現在 audit trail、log、Inertia props（除「已設定/未設定」狀態）。

### 5.2 SDK Preset — `user_provider_settings`

一 user 一 provider key 一列（unique `user_id` + `provider_key`）。

| 欄位 | 說明 |
|------|------|
| `provider_key` | 對應 [config/ai.php](../config/ai.php) 的 key（如 `openai`, `anthropic`, `gemini`, `ollama`, `groq`…） |
| `api_key` | encrypted；cloud preset **SHOULD** 只需填此欄 |
| `api_url` | nullable；預設取自 config preset；**Ollama / 本機 MUST** 可覆寫 |
| `model` | nullable |
| `provider_options` | nullable JSON；UI 以 textarea 輸入（如 `{"max_tokens":2048}`）；runtime 合併至 Laravel AI request body |
| `enabled` | boolean |

UI catalog **MUST** 由後端自 `config/ai.php` 產生 preset 列表（名稱、預設 URL）；cloud preset **SHOULD** 隱藏 URL 欄，僅顯示 key。

### 5.3 自訂 Provider — `user_custom_providers`

| 欄位 | 說明 |
|------|------|
| `label` | 顯示名稱 |
| `api_url` | 必填 |
| `api_key` | encrypted |
| `model` | nullable |
| `provider_options` | nullable JSON；UI 以 textarea 輸入（如 `{"max_tokens":2048}`）；runtime 合併至 Laravel AI request body |
| `enabled` | boolean |

### 5.4 Consensus 三槽（邏輯名不變）

Domain 仍注入三個 `LlmProvider`，邏輯名 **`openai`、`anthropic`、`gemini`**（與 [02-contracts.md §8](02-contracts.md) 一致）。

`users.consensus_slots` JSON schema（M7-B）：

```json
{
  "openai": { "type": "preset", "provider_key": "openai" },
  "anthropic": { "type": "preset", "provider_key": "anthropic" },
  "gemini": { "type": "custom", "custom_provider_id": 1 }
}
```

`type` **MUST** 為 `preset` \| `custom`。未設定或 disabled 的槽 **MUST** 在 runtime 回傳 `provider_status = provider_unavailable`（見 [02-contracts.md §4](02-contracts.md)）。

### 5.5 Runtime 解析

`ConfiguredLlmProviderFactory`（或 successor）**MUST** 提供：

```php
public function forUser(User $user): array; // 三 LlmProvider，邏輯名 openai/anthropic/gemini
public function forDemo(SystemDemoSettings $settings): array;
```

實作 **SHOULD** 使用 scoped config override 或 decorator；**MUST NOT** 修改 `app/Consensus/`。

自訂 OpenAI-compatible endpoint **SHOULD** 優先透過 Laravel AI SDK driver + custom base URL。

---

## 6. Verification 與 Audit 擴充

### 6.1 `verification_requests.user_id`

| 情境 | `user_id` |
|------|-----------|
| 訪客 Demo | `null` |
| 登入使用者驗證 | 必填 |

### 6.2 Policy

`VerificationRequestPolicy` **MUST** 限制：`user` 僅能 `view` 自己的紀錄；`admin` **MAY** `view` 全部。

### 6.3 Audit 擴充（相對 [02-contracts.md §10](02-contracts.md)）

**MUST** 新增保存：

| 欄位 | 說明 |
|------|------|
| `user_id` | nullable FK |
| `metadata.source` | `demo` \| `authenticated` |
| `metadata.demo_mode` | fake_fixtures \| shared_local_api（demo 時） |

**MUST NOT** 保存：api_key、api_token、完整 Authorization header。

---

## 7. Milestone 7 Gate 驗收

### M7-A

- [x] Fortify 安裝；register / login / logout / password reset 可運作
- [x] vue-starter-kit layouts + auth 頁移植；`npm run typecheck` 通過
- [x] `users.role` + admin middleware + admin seeder
- [x] `GET /` Welcome；`/demo/*` 保留 M6 demo 行為
- [x] **使用者可見 UI 文案為繁體中文**（§3.4）
- [x] `M7AAuthTest` + 更新 `M6MinimalUiTest`
- [x] **MUST NOT** 引入 Breeze

### M7-B

- [x] Provider settings CRUD（preset + custom）；encrypted 儲存
- [x] `forUser()` / `forDemo()` 解析三槽 provider
- [x] 登入使用者 `POST /verifications` 走真 provider（或 unavailable 降級）
- [x] Admin demo settings；兩種 demo mode
- [x] 產品 Dashboard（摘要、最近 verification、provider 就緒度）
- [x] `VerificationRequestPolicy`；`user_id` 持久化
- [x] Feature tests：`M7BProviderSettingsTest`、`M7BDemoAdminTest`、`M7BVerificationAuthTest`

---

## Traceability

| 本文件章節 | 對應 |
|------------|------|
| §1 範圍 | [00-product-vision.md §7](00-product-vision.md)、[07-milestones.md §M7](07-milestones.md) |
| §1.4 移植策略 | 本專案 Orchestrator M7 決策 |
| §2 Auth | [01-architecture.md §7](01-architecture.md)、Laravel 13 Starter Kits |
| §3 前端 | [01-architecture.md §1](01-architecture.md)、M6 實作慣例 |
| §3.4 顯示語言 | 產品決策（繁體中文 only） |
| §3.5 M6 路由 | M7-A 路由遷移 |
| §4 Demo | [00-product-vision.md §3.2](00-product-vision.md) fake fixture 策略 |
| §5 Provider | [02-contracts.md §8](02-contracts.md)、[01-architecture.md §4](01-architecture.md) |
| §6 Audit | [02-contracts.md §10–§11](02-contracts.md) |
| §7 驗收 | [07-milestones.md §M7](07-milestones.md) |

**技術決策**：Auth 用 Fortify（非 Breeze）；前端 **選擇性移植** vue-starter-kit（非 `laravel new --vue` 整包安裝），見 §1.4。
