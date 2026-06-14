# Worker Brief — Gate M7-A

**Milestone 7 · Auth 基礎 + Vue Starter Kit 移植 + Welcome + Demo 路由**  
**前置**：M6 **RELEASED**  
**狀態**：**OPEN**

> Post-MVP 第一 Gate。Auth 採 **Laravel Fortify**（L13 官方 starter kit 同款），**不用 Breeze**。前端 **選擇性移植** [laravel/vue-starter-kit](https://github.com/laravel/vue-starter-kit)（**MUST NOT** 整包安裝或 `laravel new --vue`），見 [docs/08 §1.4](../../../../docs/08-ui-auth-providers.md)。

---

## 角色

Worker Agent。**只做 M7-A**：Fortify、Inertia auth 頁、layout 基礎、角色、Welcome、Demo 路由 prefix。**M7-B** 才做 provider 設定與產品 Dashboard 完整內容。

---

## 必讀

1. **[docs/08-ui-auth-providers.md](../../../../docs/08-ui-auth-providers.md)**（M7 canonical spec）
2. [docs/07-milestones.md](../../../../docs/07-milestones.md) §M7
3. [docs/00-product-vision.md](../../../../docs/00-product-vision.md) §7
4. [Laravel 13 Starter Kits — Authentication](https://laravel.com/docs/13.x/starter-kits#authentication)
5. [laravel/vue-starter-kit](https://github.com/laravel/vue-starter-kit)
6. 本 brief · [progress.md](progress.md)

---

## 背景

- M6 僅 2 個有效 Vue 頁 + minimal inline Tailwind；**不是**完整產品前端
- 專案已有 Tailwind v4、TS、Inertia Vue 3 — 與官方 kit 技術基底一致
- **MUST 維持** [`config/inertia.php`](../../../../config/inertia.php) 的 `js/Pages`（大寫 P），避免 CI case-sensitive 回歸
- **MUST NOT** 整包安裝 starter kit：只移植 Fortify + layouts + auth/settings + 必要 ui 元件；勿保留 kit 預設 Dashboard/範例 CRUD（見 [08 §1.4](../../../../docs/08-ui-auth-providers.md)）

---

## 交付物

### 後端

- [ ] `composer require laravel/fortify` + publish config
- [ ] 移植 `app/Actions/Fortify/*`、`FortifyServiceProvider`（Inertia 版，參考 vue-starter-kit）
- [ ] `config/fortify.php`：register、login、password reset；**2FA 可關閉**至 M7-B
- [ ] `users.role` migration（`user` | `admin`）；`User::isAdmin()`
- [ ] Middleware `admin`（`EnsureUserIsAdmin`）
- [ ] Admin seeder（`ADMIN_EMAIL` / `ADMIN_PASSWORD` from env）
- [ ] `HandleInertiaRequests` 共享 `auth.user`（含 role）

### 前端（自 vue-starter-kit 移植）

- [ ] `resources/js/layouts/`（App、Auth layout）
- [ ] `resources/js/components/ui/` + 必要 shadcn-vue 依賴與 `@/` alias
- [ ] `Pages/auth/*`：Login、Register、ForgotPassword、ResetPassword
- [ ] `Pages/settings/*`：Profile、Password（kit 預設即可）
- [ ] **刪除** 孤立 `Pages/Welcome.vue`
- [ ] **新** `Pages/Home/Welcome.vue`（產品 Welcome + CTA → `/demo`、`/register`）
- [ ] Dashboard 頁：可保留 kit 殼，M7-B 再換產品內容

### 路由

- [ ] `GET /` → Welcome
- [ ] `GET /demo`、`POST /demo/verifications`、`GET /demo/verifications/{id}` → `DemoVerificationController`（M6 行為）
- [ ] Fortify auth 路由（login/register/logout…）
- [ ] `GET /dashboard` → auth + Inertia dashboard（placeholder OK）
- [ ] `GET /health` 保留

### 測試

- [ ] `tests/Feature/M7AAuthTest.php`（register、login、logout、guest redirect）
- [ ] 更新 `M6MinimalUiTest`、`ExampleTest` → `/demo` prefix
- [ ] `npm run typecheck` 通過；全 suite 綠

---

## MUST NOT

- 使用 **laravel/breeze**
- **`laravel new --vue` 或整包覆蓋 vue-starter-kit**（必須選擇性移植，見 08 §1.4）
- 保留 kit 預設 placeholder 應用（範例 CRUD、與 Verification/Demo 無關的路由/頁）
- 修改 `docs/`、根 `README.md`
- M7-B 範圍：provider DB、Admin demo settings、真 provider verification、`UserProviderFactory`
- 改動 `app/Consensus/` domain 算法
- 改 `config/inertia.php` 的 Pages 路徑為小寫 `pages`（除非 Orchestrator 明確批准並同步 CI 策略）

---

## 完成後交還

1. 更新 [progress.md](progress.md) §1–4
2. §4 列「建議 Orchestrator 文件更新」（M7 milestone 草案、README 路由、`.env.example` ADMIN_*）
3. 使用者轉交 Orchestrator 審核
