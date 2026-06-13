# Worker Brief — Gate M3-A

**Milestone 3 · Fake Provider + 並行 Raw Answer 編排**  
**前置**：M2 全 Gate **RELEASED**  
**狀態**：OPEN

> 本 Gate 合併原規劃之 M3-A（fake + F01）與 M3-B（並行 raw answer），一次交付 fake 路徑與編排骨架。

---

## 角色

Worker Agent。**只做 M3-A**：fake `LlmProvider`、F01 replay、並行 provider 查詢與 raw answer 持久化。**不接真 API**。

---

## 必讀

1. [docs/07-milestones.md](../../../../docs/07-milestones.md) Milestone 3
2. [docs/02-contracts.md](../../../../docs/02-contracts.md) §9 `LlmProvider`、`FakeProviderRegistry`
3. [docs/01-architecture.md](../../../../docs/01-architecture.md) §4（SDK 只在 `app/AI/`）
4. [docs/06-test-scenarios.md](../../../../docs/06-test-scenarios.md) F01
5. 本 brief · [progress.md](progress.md)

---

## 交付物

- [ ] **Fake `LlmProvider`**：實作 domain 契約（`app/Consensus/` 或 `app/AI/` 依 01 §4；fake **MUST NOT** 依賴 SDK）
- [ ] **`FakeProviderRegistry`**：可依 fixture id（如 `F01`）建立 fake provider
- [ ] **並行 raw answer 編排**：對多 provider 並行查詢（可先全用 fake）；單 provider 失敗不中断 pipeline
- [ ] **per-provider raw answer 持久化**：寫入 `provider_responses`（對齊 02 §10）
- [ ] **timeout / 重試**：至多重試一次（對照 07 M3 驗收）
- [ ] **測試**：fake 可 replay F01；並行 + 單 provider 失敗場景有 Feature/Unit test
- [ ] **DI**：註冊 fake registry / 編排服務（更新 `ConsensusServiceProvider` 或新增 Provider）

---

## 驗收命令（範例）

```bash
php artisan test --compact --filter=FakeProvider
php artisan test --compact --filter=ProviderOrchestration   # 或實際 test 名稱
```

progress §2 **MUST** 貼實際 filter 與輸出。

---

## MUST NOT

- 修改 `docs/`、根目錄 `README.md`
- **真 LLM / Laravel AI SDK 呼叫**（屬 **M3-B**）
- Response Extractor 實作（M4）
- Consensus 判定邏輯（M4）
- Classifier 實作（M4）

---

## 完成後交還

1. 更新 [progress.md](progress.md)（§1 勾選、§2 驗收輸出、§3 檔案清單）
2. progress §4 列 Orchestrator 需回寫 docs/README 的建議（若有）
3. 建議下一 Gate：**M3-B**
