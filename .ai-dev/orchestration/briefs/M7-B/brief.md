# Worker Brief — Gate M7-B

**Milestone 7 · Provider 設定 + Admin Demo + Dashboard + 登入 Verification**  
**前置**：M7-A **RELEASED**（含 M7-A-R1 繁中 UI）  
**狀態**：**RELEASED**（2026-06-14）

> Post-MVP 第二 Gate。使用者 **BYOK**（各自設定 API key / endpoint）；Admin 管理**訪客 Demo**（與 user provider 無關）。Domain consensus 算法 **MUST NOT** 改動。

---

## 角色

Worker Agent。**只做 M7-B**：持久化 provider 憑證、runtime 解析、產品 UI、auth verification、admin demo。

---

## 必讀

1. **[docs/08-ui-auth-providers.md §4–§7](../../../../docs/08-ui-auth-providers.md)**（Demo、Provider、Audit、驗收）
2. [docs/08-ui-auth-providers.md §3.4](../../../../docs/08-ui-auth-providers.md)（繁體中文 UI）
3. [docs/07-milestones.md §M7](../../../../docs/07-milestones.md)
4. 現有實作：`ConfiguredLlmProviderFactory`、`VerificationController`（demo）、`ConsensusWorkflow`、`Dashboard.vue`
5. 本 brief · [progress.md](progress.md)

---

## 背景（現況）

| 已有 | 缺口 |
|------|------|
| Fortify auth、Welcome、`/demo/*` fake fixture | 無 per-user provider DB |
| `ConfiguredLlmProviderFactory::all()` 讀 `.env` | 無 `forUser()` / `forDemo()` |
| `VerificationController` 僅 demo 路由 | 無 auth verification、無 policy |
| `verification_requests` 無 `user_id` | 需 migration + audit 擴充 |
| `Dashboard.vue` 占位文案 | 需產品 Dashboard |
| Admin middleware + 空 `/admin` | 需 Admin demo settings |

**產品決策（已確認）**：Provider 設定由**每位登入使用者自行設定**（非 admin 代管、非團隊共享）。見 08 §1.3、§5。

---

## 交付物

### 1. 資料庫與 Models

- [ ] Migration：`verification_requests.user_id`（nullable FK → users）
- [ ] Migration：`users.consensus_slots`（json, nullable）
- [ ] Migration + Model：`user_provider_settings`（unique `user_id` + `provider_key`）
- [ ] Migration + Model：`user_custom_providers`
- [ ] Migration + Model：`system_demo_settings`（singleton；seed 預設 `fake_fixtures` + `demo_enabled=true`）
- [ ] Eloquent：`encrypted` cast 於 `api_key` 欄位（preset + custom + demo shared key）
- [ ] `User` relations：`providerSettings()`、`customProviders()`；`consensus_slots` cast

**`user_provider_settings` 欄位**（08 §5.2）：`provider_key`, `api_key`, `api_url`, `model`, `enabled`

**`user_custom_providers` 欄位**（08 §5.3）：`label`, `api_url`, `api_key`, `model`, `enabled`

**`consensus_slots` JSON schema**（08 §5.4）：

```json
{
  "openai": { "type": "preset", "provider_key": "openai" },
  "anthropic": { "type": "preset", "provider_key": "anthropic" },
  "gemini": { "type": "custom", "custom_provider_id": 1 }
}
```

### 2. Runtime — `ConfiguredLlmProviderFactory` 擴充

在 **`app/AI/`**（**MUST NOT** 改 `app/Consensus/`）：

- [ ] `forUser(User $user): array` — 回傳三個 `LlmProvider`，邏輯名 **`openai` / `anthropic` / `gemini`**
- [ ] `forDemo(SystemDemoSettings $settings): array`
- [ ] 未設定、disabled、缺 key 的槽 → `provider_unavailable`（沿用 `LaravelAiLlmProvider` disabled 行為）
- [ ] Per-user key/url/model **SHOULD** 透過 scoped config override 或 decorator 注入 Laravel AI SDK；**MUST NOT** 把 secret 寫入 log / audit / Inertia props

**Catalog**：後端自 `config/ai.php` 產生 preset 列表（名稱、預設 URL）；cloud preset UI **SHOULD** 隱藏 URL、僅顯示 key；Ollama / 本機 **MUST** 可覆寫 URL。

### 3. HTTP 與 Policy

| 路由 | 方法 | 存取 | 說明 |
|------|------|------|------|
| `/settings/providers` | GET, PUT | auth | Provider 設定 UI + 儲存（含 consensus_slots） |
| `/admin/demo` | GET, PUT | auth + admin | Demo mode 管理 |
| `/verifications/create` | GET | auth | 問題輸入（無 fixture） |
| `/verifications` | POST | auth | 提交 verification |
| `/verifications/{id}` | GET | auth + policy | 結果頁（reuse Show 元件或共用 payload） |

- [ ] `VerificationRequestPolicy`：`user` 僅 `view` 自己的；`admin` **MAY** `view` 全部
- [ ] 訪客 demo：`user_id = null`；登入 verification：`user_id` 必填
- [ ] Audit metadata（08 §6.3）：`metadata.source` = `demo` \| `authenticated`；demo 時加 `metadata.demo_mode`
- [ ] **MUST NOT** 在 audit / metadata 保存 api_key、token

**實作提示**：`ConsensusWorkflow::run()` 在 domain 內 create record；HTTP 層在 run 後 update `user_id` 與 audit metadata 即可，**避免**改 `app/Consensus/`。

### 4. Demo 重構

- [ ] `/demo/*` 改讀 `system_demo_settings`（非硬編 catalog-only）
- [ ] `fake_fixtures`：沿用 `ConsensusDemoFixtureCatalog`；尊重 `demo_enabled`、`enabled_fixture_ids`、`default_fixture_id`
- [ ] `shared_local_api`：三槽皆指向 admin 設定的 shared url/key（如 Ollama）
- [ ] `demo_enabled=false` 時 `/demo` **SHOULD** 404 或友善關閉頁（繁中）

### 5. 前端（Inertia · 繁體中文）

- [ ] `Pages/settings/Providers.vue`（或等價）— preset + custom CRUD、三槽對應、僅顯示「已設定/未設定」不回傳 key
- [ ] `Pages/admin/DemoSettings.vue` — mode 切換、fixture 列表、shared API 欄位
- [ ] `Pages/Dashboard.vue` — 摘要、最近 verification、三槽 provider 就緒度、CTA → 新建 verification / 設定 provider
- [ ] `Pages/Verification/Create.vue` — 登入版問題輸入（無 fixture 選擇）
- [ ] 更新 `AppLayout` nav：連結「Provider 設定」；admin 可見「Demo 管理」
- [ ] Reuse `Verification/Show.vue` payload 結構（auth 路由 props 一致）

**語言**：延續 M7-A-R1；**MUST NOT** 引入 vue-i18n。

### 6. Feature Tests

- [ ] `tests/Feature/M7BProviderSettingsTest.php` — CRUD、encrypted、consensus_slots、Inertia 頁
- [ ] `tests/Feature/M7BDemoAdminTest.php` — admin 可改 demo；非 admin 403；demo mode 行為
- [ ] `tests/Feature/M7BVerificationAuthTest.php` — 登入 POST verification、policy、user_id、metadata.source

**可選（MAY）**：Email Verification 啟用（08 §2.4）；預設 **不開** 以控 scope。

---

## 驗收命令

```text
npm run typecheck
vendor/bin/pint --dirty --format agent
php artisan test --compact
php artisan test --filter=M7B
```

Spot-check（繁中 UI）：

- `/settings/providers`、`/dashboard`、`/admin/demo`
- 登入後 `/verifications/create` → 提交 → `/verifications/{id}`

---

## MUST NOT

- 改寫 `app/Consensus/` 算法或 `ConsensusWorkflow` 判定邏輯（HTTP 層可 wrap）
- 修改 `docs/`、根 `README.md`（需求寫 progress §4）
- 多租戶 / 團隊共享 provider
- API key 出現在 audit、log、Inertia props（除 boolean 狀態）
- 引入 Phase 3（grounding、RAG、語意對齊）
- 破壞 M7-A：Fortify、`/demo` fake fixture 預設行為、繁中 UI

---

## 完成後交還

1. 更新 [progress.md](progress.md) §1–4
2. §4 列「建議 Orchestrator 文件更新」（README 路由表、`.env.example` 新變數、07-milestones 狀態等）
3. 使用者轉交 Orchestrator **審核 RELEASED**

---

## 參考：建議實作順序

```text
1. Migrations + Models + Factory methods（forUser / forDemo）+ unit/feature 骨架
2. Provider settings HTTP + Vue
3. Verification auth routes + policy + metadata
4. Admin demo settings + refactor /demo
5. Dashboard 產品化
6. 全 suite 綠 + progress 回報
```
