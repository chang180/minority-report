# Worker Brief — Gate M7-A

**Milestone 7 · Auth 基礎 + Vue Starter Kit 移植 + Welcome + Demo 路由**  
**前置**：M6 **RELEASED**  
**狀態**：**RELEASED**（2026-06-14 · M7-A-R1 繁中 UI 修正完成）

> M7-A-R1 已複審放行。**M7-B brief 可發布**。

Post-MVP 第一 Gate。Auth 採 **Laravel Fortify**（L13 官方 starter kit 同款），**不用 Breeze**。前端 **選擇性移植** [laravel/vue-starter-kit](https://github.com/laravel/vue-starter-kit)（**MUST NOT** 整包安裝），見 [docs/08 §1.4](../../../../docs/08-ui-auth-providers.md)。

---

## 角色

Worker Agent。**本輪只做 M7-A-R1：繁體中文 UI 修正**（在既有 Fortify / 路由 / layout 實作上改文案，不重做架構）。

---

## 必讀

1. **[docs/08-ui-auth-providers.md §3.4](../../../../docs/08-ui-auth-providers.md)**（顯示語言規格 · **新增**）
2. [docs/08-ui-auth-providers.md](../../../../docs/08-ui-auth-providers.md) 全文
3. [docs/07-milestones.md](../../../../docs/07-milestones.md) §M7
4. 本 brief · [progress.md](progress.md)

---

## 背景

- M7-A 功能面（Fortify、路由、role、測試）已交付；**Blocking 修正**：使用者可見文案仍大量為英文（kit 預設）
- 產品決策：**唯一顯示語言為繁體中文**（見 08 §3.4）
- **MUST NOT** 引入 i18n / vue-i18n 框架（M7 階段硬編碼繁中即可）
- **MUST NOT** 改 `docs/`、根 `README.md`

---

## M7-A-R1 交付物（繁體中文 UI）

### 語言規則（摘要）

| 類別 | 語言 |
|------|------|
| 按鈕、標題、說明、nav、表單 label、placeholder、空狀態、Dashboard 文案 | **繁體中文** |
| 使用者可見 validation / 錯誤訊息 | **繁體中文**（必要時調整 `lang/zh_TW` 或 Form Request messages） |
| API / domain 參數 | **保留英文**：`openai`、`consensus`、`trust`、`provider_status`、fixture id、JSON key、model 名 |
| 路由 path、HTML `name`/`autocomplete`、程式識別符 | 維持現狀 |
| 品牌 | 「關鍵報告」；副標可保留 Minority Report 作為英文名 |

### 必須中文化（至少）

- [x] `resources/js/layouts/*`（AppLayout、AuthLayout、GuestLayout）— nav、logout、settings 等
- [x] `resources/js/Pages/auth/*`（Login、Register、ForgotPassword、ResetPassword、ConfirmPassword）
- [x] `resources/js/Pages/settings/*`（Profile、Password）
- [x] `resources/js/Pages/Dashboard.vue`
- [x] `resources/js/Pages/Home/Welcome.vue`（若有英文段落）
- [x] `resources/js/Pages/Verification/Index.vue`、`Show.vue`（M6 英文標題/label 一併改繁中）
- [x] Fortify / Laravel 回傳之**使用者可見** flash、validation（`lang/zh_TW` 或自訂 messages）
- [x] `config/app.php` 或 `.env`：`APP_LOCALE=zh_TW`、`APP_FALLBACK_LOCALE=zh_TW`（本機 example 由 Orchestrator 整合；Worker 可於 progress §4 建議）

### 測試

- [x] 更新 `M7AAuthTest` / `M6MinimalUiTest`：若 assert 英文 Inertia 文案，改為繁中或改 assert 結構（component/route）避免 brittle
- [x] `npm run typecheck`、全 suite 綠

### 本輪 MUST NOT

- M7-B 範圍（provider DB、Admin demo、真 verification）
- 改動 `app/Consensus/` domain
- 引入多語系框架或英文 fallback UI

---

## 原 M7-A 交付物（已存在 · 勿 regress）

以下 **MUST** 保持可用，修正文案時不得破壞：

- Fortify + Inertia auth 流程
- `users.role` + admin middleware
- `/` Welcome、`/demo/*` demo 路由
- `M7AAuthTest` 核心 auth 行為
- 選擇性移植 kit 結構（非整包安裝）

---

## 完成後交還

1. 更新 [progress.md](progress.md) §1–4（標明 M7-A-R1 繁中修正）
2. §4 列「建議 Orchestrator 文件更新」（若需 README 語言說明、`APP_LOCALE` example）
3. 使用者轉交 Orchestrator **重新審核 RELEASED**
