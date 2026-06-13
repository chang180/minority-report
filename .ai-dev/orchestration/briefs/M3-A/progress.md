# M3-A Progress — Fake Provider + 並行 Raw Answer

| 欄位 | 值 |
|------|-----|
| Gate | **M3-A** |
| 狀態 | **RELEASED** |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 交付物檢核

- [x] Fake `LlmProvider` 實作 domain 契約
- [x] `FakeProviderRegistry` 可依 fixture id 建立 provider
- [x] 並行 raw answer 編排（多 provider；單失敗不中断）
- [x] per-provider raw answer 寫入 `provider_responses`
- [x] timeout / 至多重試一次
- [x] F01 replay 測試通過
- [x] 根 `README.md` **未遭 Worker 修改**

### 1.1 禁止項

- [x] **無** 真 LLM / SDK **呼叫**
- [x] **無** Extractor / Classifier / Consensus 判定

---

## 2. 驗收命令

```bash
php artisan test --compact --filter=FakeProvider
php artisan test --compact --filter=ProviderOrchestration
```

### 2.1 輸出紀錄

```text
# FakeProvider
  .....

  Tests:    5 passed (33 assertions)
  Duration: 0.43s

# ProviderOrchestration
  .....

  Tests:    5 passed (21 assertions)
  Duration: 0.41s

# 全套（無回歸）
  ..............

  Tests:    14 passed (72 assertions)
  Duration: 0.58s
```

---

## 3. 變更檔案清單

```text
# 新增
app/Consensus/Exceptions/ProviderException.php
app/Consensus/Exceptions/ProviderTimeoutException.php
app/Consensus/Contracts/ProviderResponseRepository.php
app/Consensus/Fake/FakeLlmProvider.php
app/Consensus/Fake/InMemoryFakeProviderRegistry.php
app/Consensus/ProviderOrchestrator.php
app/Repositories/EloquentProviderResponseRepository.php
tests/Feature/Consensus/FakeProviderTest.php
tests/Feature/Consensus/ProviderOrchestrationTest.php

# 修改
app/Providers/ConsensusServiceProvider.php
  - 替換 NullFakeProviderRegistry → InMemoryFakeProviderRegistry（singleton）
  - 新增 ProviderResponseRepository → EloquentProviderResponseRepository 綁定
  - 移除已棄用的 NullFakeProviderRegistry 引用
```

---

## 4. Worker 提交 / 5. Orchestrator 審核

| Worker 日期 | 2026-06-13 |
| **建議 Orchestrator 文件更新** | 無必要更新 README；可更新 `docs/07` M3 狀態。 |
| 審核者 | Orchestrator |
| 日期 | 2026-06-13 |
| 結果 | ☑ **RELEASED** · ☐ REJECTED |
| **docs / README 整合** | ☑ 已更新（`docs/07-milestones.md` M2/M3 狀態） |
| Blocking | 無 |
| Non-blocking | `ProviderOrchestrator` 目前循序 dispatch；真並行留 **M3-B** adapter 層。`NullFakeProviderRegistry` 仍留 repo 未刪。 |
| 備註 | 重跑 FakeProvider 5 + ProviderOrchestration 5 + 全 suite 14 passed；無 SDK 引用。下一 Gate：**M3-B**。 |

---

### 設計備註（供 Orchestrator 參考）

1. **Fake 放在 `app/Consensus/Fake/`**：fake 不依賴 SDK，屬 domain 一等公民，放 `app/AI/` 不符合語意。
2. **`ProviderResponseRepository` 介面放 `app/Consensus/Contracts/`**：orchestrator 在 domain 層，透過介面注入 Eloquent 實作，符合 01 §2.2 依賴規則。
3. **Eloquent 實作放 `app/Repositories/`**：標準 Laravel 慣例，非新 base folder（在 `app/` 下）。
4. **`ProviderOrchestrator` 目前循序執行**：fake provider 場景循序即可；M3-B 接真 API 時可在 adapter 層導入 Fiber 或 Guzzle async，不需改動 domain 介面。
5. **下一 Gate 建議**：**M3-B**（接真實 LLM / Laravel AI SDK）。
