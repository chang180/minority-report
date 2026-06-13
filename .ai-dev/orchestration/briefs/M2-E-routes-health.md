# Worker Brief — Gate M2-E

**Milestone 2 · Routes + 健康檢查 + M2 整體驗收**  
**前置 Gate**：M2-A、M2-B、M2-C、M2-D 皆 **RELEASED**  
**狀態**：BLOCKED

---

## 角色

Worker Agent。**只做 M2-E**：最小 HTTP 路由、健康檢查、確認 M2 驗收項可過。

---

## 必讀

1. [docs/07-milestones.md](../../../docs/07-milestones.md) Milestone 2 驗收
2. [handoff.md](../handoff.md)
3. 本 brief

---

## 交付物

- [ ] `GET /` 或 `GET /health` 回傳 JSON：`{ "status": "ok", "app": "minority-report", "laravel": "13.x" }`
- [ ] 路由定義於 `routes/web.php` 或 `routes/api.php`（擇一，README 註明）
- [ ] **不**實作問題提交 / consensus UI（M6）
- [ ] 更新根 [README.md](../../../README.md) Development：如何啟動 `php artisan serve` 並 curl 健康檢查
- [ ] （可選）Feature test：`tests/Feature/HealthCheckTest.php`

---

## M2 整體驗收清單（本 Gate 須全過）

對照 [07-milestones.md](../../../docs/07-milestones.md) M2：

- [ ] `php artisan` 可執行
- [ ] domain 目錄符合 01-architecture §2
- [ ] **MUST NOT** 在此 milestone 完成 consensus 邏輯（僅 skeleton + stub）

```bash
composer install
php artisan --version
php artisan migrate --force
php artisan serve &
curl -s http://127.0.0.1:8000/health   # 或實際路徑
php artisan test --filter=HealthCheck   # 若有
```

---

## MUST NOT

- Consensus workflow endpoint（M6）
- 真 LLM 呼叫（M3）
- Classifier 實作（M4）

---

## 完成後交還

1. curl / test 輸出
2. M2 驗收 checklist 逐項勾選
3. 建議 M3 第一 Gate：**M3-A fake provider**

Orchestrator 放行 M2-E 後，Milestone 2 標記 **RELEASED**，開 M3 briefs。
