# Worker Brief — Gate M3-B

**Milestone 3 · Laravel AI SDK Adapter（OpenAI + Claude + Gemini）**  
**前置 Gate**：**M3-A RELEASED**  
**狀態**：OPEN

> 本 Gate 合併原規劃之 M3-C（OpenAI adapter）與 M3-D（Claude + Gemini）。三 backend 一次交付；未就緒 key 可仍以 fake 頂替（07 M3）。

---

## 角色

Worker Agent。**只做 M3-B**：`app/AI/Providers/*` bridge Laravel AI SDK → domain `LlmProvider`。

---

## 必讀

1. [docs/07-milestones.md](../../../../docs/07-milestones.md) Milestone 3
2. [docs/01-architecture.md](../../../../docs/01-architecture.md) §4 AI Infrastructure
3. [docs/02-contracts.md](../../../../docs/02-contracts.md) §9 `LlmProvider`
4. `config/ai.php`、`config/consensus.php`
5. 本 brief · [progress.md](progress.md)

---

## 交付物

- [ ] **`app/AI/Providers/*`**：SDK adapter 實作 `LlmProvider`（domain **MUST NOT** 直接 use SDK facade）
- [ ] **OpenAI** backend adapter
- [ ] **Claude** backend adapter
- [ ] **Gemini** backend adapter
- [ ] DI：依 `config/consensus.php` provider 設定 wiring；缺 key 時 graceful degrade（fake 或 `provider_unavailable`）
- [ ] **測試**：adapter 單元測試（可 mock HTTP / SDK）；至少一條整合路徑（可 opt-in、需 env key）

---

## MUST NOT

- 修改 `docs/`、根目錄 `README.md`（需求寫 progress §4）
- 在 `app/Consensus/` 直接呼叫 Laravel AI SDK
- Extractor / Classifier / Consensus 算法（M4）

---

## 完成後交還

1. 更新 [progress.md](progress.md)
2. M3 milestone 完成後 Orchestrator 更新 gate-status **M3 RELEASED**
