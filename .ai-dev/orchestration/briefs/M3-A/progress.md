# M3-A Progress — Fake Provider + 並行 Raw Answer

| 欄位 | 值 |
|------|-----|
| Gate | **M3-A** |
| 狀態 | **OPEN** |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 交付物檢核

- [ ] Fake `LlmProvider` 實作 domain 契約
- [ ] `FakeProviderRegistry` 可依 fixture id 建立 provider
- [ ] 並行 raw answer 編排（多 provider；單失敗不中断）
- [ ] per-provider raw answer 寫入 `provider_responses`
- [ ] timeout / 至多重試一次
- [ ] F01 replay 測試通過
- [ ] 根 `README.md` **未遭 Worker 修改**

### 1.1 禁止項

- [ ] **無** 真 LLM / SDK **呼叫**
- [ ] **無** Extractor / Classifier / Consensus 判定

---

## 2. 驗收命令

```bash
# Worker 填實際 filter
php artisan test --compact --filter=...
```

### 2.1 輸出紀錄

```text
（Worker 貼輸出）
```

---

## 3. 變更檔案清單

```text
（Worker 填）
```

---

## 4. Worker 提交 / 5. Orchestrator 審核

| Worker 日期 | |
| **建議 Orchestrator 文件更新** | |
| Orchestrator 結果 | ☐ RELEASED · ☐ REJECTED |
| **docs / README 整合** | ☐ 已更新 · ☐ N/A |
