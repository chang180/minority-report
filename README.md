# 關鍵報告 · Minority Report

> **Disagreement is a feature, not a bug.**  
> 多模型交叉驗證引擎——不只要答案，還要共識、分歧、少數意見，以及可稽核的信任等級。

當 OpenAI、Claude、Gemini 對同一問題給出不同說法時，多數系統會悄悄選一個答案糊弄過去。  
**關鍵報告** 的做法相反：保留少數意見、用確定性規則裁決共識，並明確告訴你「這答案有多可信、為什麼不可全信」。

靈感來自電影《Minority Report》——少數預測往往才是關鍵線索。

---

## 這不是什麼

- 不是「三個模型投票，多數贏」的簡化版
- 不是用第四個 LLM 當裁判（避免新的單點幻覺）
- 不是假裝共識等於正確（Consensus ≠ Correctness）

## 這是什麼

一套 **Multi-LLM Consensus Engine**（Laravel 13），完成：

```text
Question → Classification → Multi-Provider Answers → Independent Extraction
    → Claim Alignment → Deterministic Consensus → Trust Level → Verdict Report
```

核心能力（MVP 規格已完稿，實作進行中）：

| 能力 | 說明 |
|------|------|
| **多模型驗證** | 同一問題並行詢問多家 LLM，各自獨立抽取結構化 claims |
| **少數意見報告** | 2 vs 1 分歧時產出 Minority Report，不抹平異議 |
| **信任等級** | `High / Medium / Low / Unknown`，base + caps 瀑布，拒絕假精確百分比 |
| **棄權處理** | `unknown` 是棄權，不是反對票——不會產出「少數意見：我不知道」 |
| **可稽核** | 完整 audit trail，可重播、可 regression test |

---

## 技術棧

| 項目 | 選型 |
|------|------|
| Framework | **Laravel 13** |
| PHP | 8.4+ |
| Database | SQLite（MVP）/ MySQL |
| Frontend | **Vue 3** + **Inertia.js** + **TypeScript** + Tailwind CSS 4 |
| Testing | **Pest**（TDD；CI 於 push/PR 自動執行） |
| AI 開發規範 | **Laravel Boost**（guidelines / skills / MCP） |
| AI Infrastructure | Laravel AI SDK（介面層；domain 不綁 vendor） |
| Providers | OpenAI · Anthropic · Gemini + **fake provider**（測試一等公民） |

---

## 專案狀態

| Milestone | 狀態 |
|-----------|------|
| M1 Spec Documents | ✅ 完成 |
| M2 Laravel Skeleton | 🚧 進行中（**M2-A** ✅ · **M2-B** 下一步） |
| M3 Provider Integration | 待開始 |
| M4 Consensus Engine | 待開始 |
| M5 Audit Trail | 待開始 |
| M6 Minimal UI | 待開始 |

目前 repo 已有 **Laravel 13 應用骨架**（SQLite、Vue + Inertia + TypeScript、Pest、GitHub Actions CI）。Consensus 業務邏輯尚未實作；所有行為仍以 spec 為準。

---

## 文件

| 路徑 | 內容 |
|------|------|
| [docs/README.md](docs/README.md) | Spec 索引與術語表 |
| [docs/00-product-vision.md](docs/00-product-vision.md) | 產品願景與 MVP 邊界 |
| [docs/01-architecture.md](docs/01-architecture.md) | 架構與模組邊界 |
| [docs/02-contracts.md](docs/02-contracts.md) | DTO、Interface、Audit 契約 |
| [docs/03-consensus-algorithm.md](docs/03-consensus-algorithm.md) | 共識演算法 Cases 1–6 |
| [docs/04-trust-level.md](docs/04-trust-level.md) | Trust base + caps 瀑布 |
| [docs/05-failure-modes.md](docs/05-failure-modes.md) | 失敗模式狀態機 |
| [docs/06-test-scenarios.md](docs/06-test-scenarios.md) | Fixture F01–F14、CT-G 測試 |
| [docs/07-milestones.md](docs/07-milestones.md) | 開發里程碑 |

開發者交接：[.ai-dev/orchestration/handoff.md](.ai-dev/orchestration/handoff.md) · M2 派工：[.ai-dev/orchestration/briefs/](.ai-dev/orchestration/briefs/)

---

## 開發

### 需求

- PHP **8.4+**、Composer 2.x
- Node.js **22+**、npm（Vue + Inertia 前端）
- SQLite（MVP 預設；本地需建立 `database/database.sqlite`）

### 首次設定

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate

npm install
npm run build          # 或開發時 npm run dev
```

### 日常開發

```bash
composer dev           # artisan serve + queue + pail + vite（一鍵）
# 或分開：
php artisan serve
npm run dev
```

### 測試（TDD）

本專案使用 **Pest**。新功能請先寫（或更新）測試，再實作程式碼；合併前須通過 `php artisan test`。

```bash
php artisan test                    # 全 suite
php artisan test --filter=welcome   # 單一測試
./vendor/bin/pest                   # 直接使用 Pest CLI
```

CI：`.github/workflows/tests.yml` 於 `main` 的 push / PR 自動執行 `composer install` → migrate → `npm ci` → **`npm run typecheck`** → `npm run build` → `php artisan test`。

### 前端（Vue + Inertia + TypeScript）

前端預設使用 **TypeScript**（`.ts` 入口、Vue SFC 使用 `<script setup lang="ts">`）。

- 入口：`resources/js/app.ts`
- 頁面元件：`resources/js/Pages/*.vue`
- 型別宣告：`resources/js/types/env.d.ts`
- 設定：`tsconfig.json`、`vite.config.ts`
- 根模板：`resources/views/app.blade.php`
- 路由回傳：`Inertia::render('PageName', [...])`（見 `routes/web.php`）

```bash
npm run typecheck   # vue-tsc 靜態型別檢查（CI 會跑）
npm run dev         # Vite 開發伺服器
npm run build       # 正式建置
```

M6 UI 將在此堆疊上擴充。

### Laravel Boost（AI 協作規範）

已安裝 `laravel/boost`。換機或升級主要套件後可更新 guidelines / skills：

```bash
php artisan boost:install --guidelines --skills --mcp --no-interaction
php artisan boost:update
```

產物含 `AGENTS.md`、`boost.json`、`.cursor/` 等，已納入版控，供 Cursor / Claude Code / Codex 等 agent 共用 Laravel 慣例。

### LLM API Keys

`.env.example` 已占位 `OPENAI_API_KEY`、`ANTHROPIC_API_KEY`、`GEMINI_API_KEY`（可空；**M3 前不呼叫**）。

---

貢獻或協作前請先閱讀 `docs/02-contracts.md` 與 `.ai-dev/orchestration/handoff.md` 中的 **Top 10 硬性規則**。

---

## License

[MIT License](LICENSE) — 可自由使用、修改與散布；軟體按「現狀」提供，不提供任何明示或默示擔保。
