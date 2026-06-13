# M2-E Progress — Routes + M2 整體驗收

| 欄位 | 值 |
|------|-----|
| Gate | **M2-E** |
| 狀態 | **BLOCKED**（待 M2-A–D 皆 RELEASED） |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 交付物檢核

- [ ] 健康檢查路由：`GET /health` 或 `GET /`（路徑：________）
- [ ] JSON 含 `status`、`app`（minority-report）、`laravel` 版本
- [ ] 根 `README.md` **未遭 Worker 修改**（serve + curl 由 Orchestrator 整合）
- [ ] （可選）`tests/Feature/HealthCheckTest.php`

### 1.1 M2 整體（07-milestones）

- [ ] `php artisan` 可執行
- [ ] domain 目錄符合 01-architecture §2
- [ ] **無** consensus 判定邏輯（僅 skeleton + stub）

### 1.2 禁止項

- [ ] **無** 問題提交 / consensus UI endpoint（M6）
- [ ] **無** 真 LLM / Laravel AI SDK **呼叫**（M3；`laravel/ai` 已在 M2-A 安裝）

---

## 2. 驗收命令

```bash
composer install
php artisan --version
php artisan migrate --force
php artisan serve &
curl -s http://127.0.0.1:8000/health
php artisan test --filter=HealthCheck
```

### 2.1 輸出紀錄

```text
（Worker 貼 curl JSON + test 結果）
```

---

## 3. M2 Milestone 總驗收

| Gate | progress.md 狀態 |
|------|------------------|
| M2-A | ☐ RELEASED |
| M2-B | ☐ RELEASED |
| M2-C | ☐ RELEASED |
| M2-D | ☐ RELEASED |
| M2-E | ☐ RELEASED |

---

## 4. Worker 提交 / 5. Orchestrator 審核

| Worker 日期 | |
| **建議 Orchestrator 文件更新** | （健康檢查 URL、curl 範例、README Development 段落） |
| Orchestrator 結果 | ☐ RELEASED · ☐ REJECTED |
| **docs / README 整合** | ☐ 已更新 · ☐ N/A |
| M2 Milestone | ☐ 標記 RELEASED（gate-status + 07） |
