# M2-A Progress — Laravel 13 專案初始化

| 欄位 | 值 |
|------|-----|
| Gate | **M2-A** |
| 狀態 | **RELEASED** |
| 前置 | M1 RELEASED |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

> **Worker**：完成一項勾一項，並在 §4 填寫證據。  
> **Orchestrator**：僅依本表 + 程式碼審核，通過後改狀態為 **RELEASED** 並更新 gate-status.md。

---

## 1. 交付物檢核（檔案 / 設定）

### 1.1 Laravel 骨架（根目錄）

- [x] `composer.json` — `laravel/framework` **^13.0**、`php` **^8.4**
- [x] `artisan` 可執行
- [x] `bootstrap/app.php`（或 Laravel 13 等價 bootstrap）
- [x] `bootstrap/providers.php` 存在
- [x] `config/app.php` 等核心 config
- [x] `routes/web.php`（或 `routes/api.php`）存在
- [x] `public/index.php`
- [x] `app/Http/Controllers/` 存在（Laravel 預設即可）
- [x] `database/` 目錄（migrations 占位可空）

### 1.2 環境與 ignore

- [x] `.env.example` 含 `APP_*` 標準項
- [x] `.env.example` 含 `OPENAI_API_KEY=`、`ANTHROPIC_API_KEY=`、`GEMINI_API_KEY=`（可空）
- [x] `.env.example` 含 `DB_CONNECTION=sqlite`
- [x] `.gitignore` 合併 Laravel 13 慣例（**未**刪除對 `docs/`、`.ai-dev/` 的追蹤）

### 1.3 既有 repo 資產（Worker 不可改）

- [x] `docs/`、`.ai-dev/`、`LICENSE` **內容未遭 Worker 修改**
- [x] 根 `README.md` Worker 階段未改；**Lead 授權**後已更新 Development（見 §1.4）

### 1.4 Orchestrator / Lead 文件

- [x] 根 `README.md` **Development** 含可執行步驟、Vue+Inertia、Pest/TDD、CI、Boost
- [x] （若需）`docs/` 與實作一致之回寫已完成 — Orchestrator 已更新 `01-architecture.md` §1 Tech Stack

### 1.5 禁止項（必須為空或不存在）

- [x] **無** `app/Consensus/` 業務實作（M2-B）
- [x] **無** `config/consensus.php`（M2-C）
- [x] **未**安裝 `laravel/ai` 或 consensus 相關套件（M3/M4）
- [x] **無** consensus 算法 / Classifier / Extractor 實作

### 1.6 額外（Lead 追加 · 2026-06-13）

- [x] `laravel/boost` **^2.4** 已加入 `require-dev`；Boost 產物**已納入版控**
- [x] Boost skills：`laravel-best-practices`、`pest-testing`、`inertia-vue-development`、`tailwindcss-development`
- [x] **Vue 3 + Inertia.js**（`inertiajs/inertia-laravel`、`HandleInertiaRequests`、`Welcome.vue`）
- [x] **Pest** 取代 PHPUnit（`pestphp/pest` + `pest-plugin-laravel`；`tests/Pest.php`）
- [x] **GitHub Actions** `.github/workflows/tests.yml`（composer + migrate + npm typecheck/build + test）
- [x] **TDD** 流程寫入 README Development
- [x] **TypeScript** 前端預設（`app.ts`、`tsconfig.json`、`vue-tsc`；CI 跑 `npm run typecheck`）

---

## 2. 驗收命令（Worker 貼原始輸出）

```bash
composer install
cp .env.example .env    # 若本地尚無 .env
php artisan key:generate
php artisan --version   # 須顯示 Laravel 13.x
php artisan about
```

### 2.1 輸出紀錄

```text
$ composer install
（略 — 107 packages installed, package:discover OK）

$ cp .env.example .env

$ php artisan key:generate

   INFO  Application key set successfully.

$ php artisan --version
Laravel Framework 13.15.0

$ php artisan about

  Environment ........................................................................................................
  Application Name ................................................................................... Minority Report
  Laravel Version ............................................................................................ 13.15.0
  PHP Version ................................................................................................. 8.4.22
  Composer Version ............................................................................................. 2.9.5
  Environment .................................................................................................. local
  Debug Mode ................................................................................................. ENABLED
  URL ...................................................................................................... localhost
  Maintenance Mode ............................................................................................... OFF
  Timezone ....................................................................................................... UTC
  Locale .......................................................................................................... en

  Cache ...............................................................................................................
  Config .................................................................................................. NOT CACHED
  Events .................................................................................................. NOT CACHED
  Routes .................................................................................................. NOT CACHED
  Views ....................................................................................................... CACHED

  Drivers .............................................................................................................
  Broadcasting ................................................................................................... log
  Cache ..................................................................................................... database
  Database .................................................................................................... sqlite
  Logs ................................................................................................ stack / single
  Mail ........................................................................................................... log
  Queue ..................................................................................................... database
  Session ................................................................................................... database

  Storage .............................................................................................................
  D:\coding\minority-report\public\storage ................................................................ NOT LINKED

$ php artisan test
Tests: 2 passed (11 assertions)   # Pest + assertInertia(Welcome)
```

---

## 3. 變更檔案清單

```text
新增（Laravel 13 骨架）:
  app/, artisan, bootstrap/, config/, database/, public/, resources/, routes/, storage/, tests/
  composer.json, composer.lock, package.json, phpunit.xml, vite.config.js
  .editorconfig, .env.example, .gitattributes

修改:
  .gitignore（合併 Laravel 13 慣例 + sqlite 本地 DB 忽略）

新增（Laravel Boost · AI 規範）:
  boost.json, .mcp.json, AGENTS.md, CLAUDE.md, opencode.json
  .agents/, .claude/, .codex/, .cursor/

新增（Lead 追加）:
  inertiajs/inertia-laravel, app/Http/Middleware/HandleInertiaRequests.php
  resources/views/app.blade.php, resources/js/Pages/Welcome.vue
  tests/Pest.php, .github/workflows/tests.yml
  package-lock.json

修改:
  README.md（Development 完整步驟）
  routes/web.php, bootstrap/app.php, resources/js/app.ts, vite.config.ts, package.json
  tsconfig.json, tsconfig.node.json, resources/js/types/env.d.ts
  tests/Unit/ExampleTest.php, tests/Feature/ExampleTest.php（Pest 語法）
  composer.json / composer.lock（inertia, pest；移除 phpunit 直依）

删除:
  resources/views/welcome.blade.php（改 Inertia）

未納入 git（本地）:
  .env, vendor/, database/*.sqlite
```

---

## 4. Worker 提交

| 項目 | 內容 |
|------|------|
| 提交者 | Worker Agent（Cursor）+ Lead 追加整合 |
| 日期 | 2026-06-13 |
| PR / commit | `b3d7110`、`ca18969` on `main` |
| 留給 M2-B | `app/Consensus/` 目錄骨架 + 空 interface 占位；`app/AI/Providers/` 占位；Service provider wiring |
| **Lead 決策（已實作）** | Boost 產物全 commit；Vue+Inertia；Pest；CI+TDD 寫入 README |
| **建議 Orchestrator 文件更新** | README 已更新；`docs/01-architecture.md` §1 可補 Frontend: Vue+Inertia（非 blocking） |

---

## 5. Orchestrator 審核

| 項目 | 內容 |
|------|------|
| 審核者 | Orchestrator |
| 日期 | 2026-06-13 |
| 結果 | ☑ **RELEASED** · ☐ REJECTED |
| Blocking 項 | 無 |
| **docs / README 整合** | ☑ 已更新（README Development · `docs/01-architecture.md` §1） |
| 備註 | 驗收重跑：`artisan --version` 13.15.0、`about` sqlite、`test` 2 passed、`npm run typecheck` OK。Lead 追加（Boost / Inertia / Pest / CI / TS）均在 M2-A 邊界內，未觸及 Consensus 業務。下一 Gate：**M2-B**。 |
