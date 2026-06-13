# M2-A Progress — Laravel 13 專案初始化

| 欄位 | 值 |
|------|-----|
| Gate | **M2-A** |
| 狀態 | **OPEN** |
| 前置 | M1 RELEASED |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

> **Worker**：完成一項勾一項，並在 §4 填寫證據。  
> **Orchestrator**：僅依本表 + 程式碼審核，通過後改狀態為 **RELEASED** 並更新 gate-status.md。

---

## 1. 交付物檢核（檔案 / 設定）

### 1.1 Laravel 骨架（根目錄）

- [ ] `composer.json` — `laravel/framework` **^13.0**、`php` **^8.4**
- [ ] `artisan` 可執行
- [ ] `bootstrap/app.php`（或 Laravel 13 等價 bootstrap）
- [ ] `bootstrap/providers.php` 存在
- [ ] `config/app.php` 等核心 config
- [ ] `routes/web.php`（或 `routes/api.php`）存在
- [ ] `public/index.php`
- [ ] `app/Http/Controllers/` 存在（Laravel 預設即可）
- [ ] `database/` 目錄（migrations 占位可空）

### 1.2 環境與 ignore

- [ ] `.env.example` 含 `APP_*` 標準項
- [ ] `.env.example` 含 `OPENAI_API_KEY=`、`ANTHROPIC_API_KEY=`、`GEMINI_API_KEY=`（可空）
- [ ] `.env.example` 含 `DB_CONNECTION=sqlite`
- [ ] `.gitignore` 合併 Laravel 13 慣例（**未**刪除對 `docs/`、`.ai-dev/` 的追蹤）

### 1.3 既有 repo 資產（Worker 不可改）

- [ ] `docs/`、`.ai-dev/`、`LICENSE` **內容未遭 Worker 修改**
- [ ] 根 `README.md` **未遭 Worker 修改**

### 1.4 Orchestrator 文件（放行時由 Lead 勾選，Worker 略過）

- [ ] 根 `README.md` **Development** 含可執行步驟（`composer install`、`cp .env.example .env`、`key:generate`）
- [ ] （若需）`docs/` 與實作一致之回寫已完成

### 1.5 禁止項（必須為空或不存在）

- [ ] **無** `app/Consensus/` 業務實作（M2-B）
- [ ] **無** `config/consensus.php`（M2-C）
- [ ] **未**安裝 `laravel/ai` 或 consensus 相關套件（M3/M4）
- [ ] **無** consensus 算法 / Classifier / Extractor 實作

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
（Worker 貼上終端輸出）
```

---

## 3. 變更檔案清單

```text
（Worker 列出新增/修改路徑）
```

---

## 4. Worker 提交

| 項目 | 內容 |
|------|------|
| 提交者 | |
| 日期 | |
| PR / commit | |
| 留給 M2-B | |
| **建議 Orchestrator 文件更新** | （例：README Development 應含哪些命令；docs 哪段需對齊實作） |

---

## 5. Orchestrator 審核

| 項目 | 內容 |
|------|------|
| 審核者 | Orchestrator |
| 日期 | |
| 結果 | ☐ RELEASED · ☐ REJECTED |
| Blocking 項 | |
| **docs / README 整合** | ☐ 已更新 · ☐ N/A |
| 備註 | |
