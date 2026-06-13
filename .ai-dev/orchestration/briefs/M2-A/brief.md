# Worker Brief — Gate M2-A

**Milestone 2 · Laravel 13 專案初始化**  
**前置 Gate**：M1 RELEASED  
**狀態**：**RELEASED**（2026-06-13；見 [gate-status.md](../../gate-status.md)）

---

## 角色

你是「關鍵報告」Worker Agent。**只做 M2-A**，不做 Consensus 業務邏輯、不做 M2-B 以後的 Gate。

---

## 必讀（按順序）

1. [handoff.md](../handoff.md) — Top 10 硬性規則
2. [docs/01-architecture.md](../../../../docs/01-architecture.md) §1 Tech Stack
3. [docs/07-milestones.md](../../../../docs/07-milestones.md) — Milestone 2 驗收
4. 本 brief · 進度追蹤：[progress.md](progress.md)（**完成後 MUST 更新**）

---

## 背景

Repo **已有**根目錄 `README.md`、`LICENSE`、`docs/`、`.ai-dev/`。**不可覆蓋或刪除**這些內容。需將 Laravel 13 **合併進現有 repo**，而非在空目錄從頭 `create-project .`。

---

## 交付物

- [ ] Laravel **13** 應用骨架（`composer.json`、`artisan`、`app/`、`bootstrap/`、`config/`、`routes/`、`public/` 等）
- [ ] PHP **8.4+** 約束（`composer.json` `require.php`）
- [ ] `.env.example` 含占位：
  - `APP_*` 標準項
  - `OPENAI_API_KEY=`、`ANTHROPIC_API_KEY=`、`GEMINI_API_KEY=`（可空，**M3 前不呼叫**）
  - `DB_CONNECTION=sqlite`（MVP 預設）
- [ ] `.gitignore` 與 Laravel 13 慣例一致（保留/合併現有 ignore 規則）
- [ ] **（Lead 選項 · 本 repo 已採用）** `laravel/ai`：publish `config/ai.php`、migrate、`boost:install` 同步 `ai-sdk-development` skill
- [ ] **不在本 Gate 改**根 `README.md` / `docs/`（Development 步驟等由 Orchestrator 於放行時整合；需求寫 progress §4）

---

## MUST NOT

- 實作 `app/Consensus/` 業務邏輯（屬 **M2-B**）
- 實作 consensus 算法、Classifier、Extractor（屬 **M4**）
- 在 `app/Consensus/` **呼叫** Laravel AI SDK 或真 LLM API（屬 **M3**）
- 刪改 `docs/`、`.ai-dev/`、`LICENSE` 語意
- **修改** `docs/`、根目錄 `README.md`（Orchestrator 專責；Worker 僅在 progress §4 建議）

> **邊界澄清**：**安裝** `laravel/ai`（composer + config + migration）**允許在 M2-A**；**使用** SDK 實作 Provider adapter / 對外 API 呼叫仍屬 **M3**。

---

## 建議做法

```bash
# 範例：在子目錄建立後合併（勿直接 create-project . 覆蓋現有檔）
composer create-project laravel/laravel:^13.0 _laravel_tmp --prefer-dist
# 將 _laravel_tmp 內容合併至 repo 根（排除其 .git、README 若衝突則保留本 repo README）
# 刪除 _laravel_tmp
composer install
cp .env.example .env
php artisan key:generate
php artisan --version

# （Lead 選項）Laravel AI SDK — 僅套件與設定，M3 前不呼叫
composer require laravel/ai
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
php artisan migrate
php artisan boost:install --guidelines --skills --mcp --no-interaction
```

SQLite：確保 `database/database.sqlite` 可選建立，或文件說明 `touch database/database.sqlite`。

---

## 驗收（Worker 自行跑完再交件）

```bash
composer install
cp .env.example .env   # 若尚未有 .env
php artisan key:generate
php artisan --version  # 須為 Laravel 13.x
php artisan about
```

---

## 完成後交還使用者

1. 更新本 Gate [progress.md](progress.md)（§1–3 + **§4 建議 Orchestrator 文件更新**）
2. 上述驗收命令輸出
3. **已知限制 / 留給 M2-B**

使用者將 **progress.md + 摘要** 轉交 **Orchestrator** 審核放行。
