# Worker Brief — Gate M4-A

**Milestone 4 · Question Classifier + Response Extractor**  
**前置**：M3 全 Gate **RELEASED**  
**狀態**：**RELEASED**（2026-06-13）

> 合併原規劃 M4-A（Classifier）與 M4-B（Extractor）。Consensus 核心判定（Aligner / Analyzer / Trust）屬 **M4-B**。

---

## 角色

Worker Agent。**只做 M4-A**：問題分類與**逐 provider 獨立** Response Extractor；含 CT-G fail-safe 測試。

---

## 必讀

1. [docs/07-milestones.md](../../../../docs/07-milestones.md) Milestone 4
2. [docs/02-contracts.md](../../../../docs/02-contracts.md) §9 `QuestionClassifier`、`ResponseExtractor`
3. [docs/06-test-scenarios.md](../../../../docs/06-test-scenarios.md) §4 CT-G1–G3
4. [handoff.md](../../handoff.md) Top 10（#2 逐 provider 獨立 extractor）
5. 本 brief · [progress.md](progress.md)

---

## 交付物

- [ ] **`QuestionClassifier` 實作**（替換 Null stub）；含 fail-safe bias
- [ ] **`ResponseExtractor` 實作**（替換 Null stub）；**MUST** 逐 provider 獨立呼叫
- [ ] 更新 `provider_responses.extraction_status` / `normalized` 持久化
- [ ] **CT-G1–G3** 單元測試通過（06 §4）
- [ ] DI wiring（`ConsensusServiceProvider`）
- [ ] 測試可先用 fake provider / fixture replay（延續 M3-A registry）

---

## MUST NOT

- 修改 `docs/`、根目錄 `README.md`
- Claim Aligner / Consensus Analyzer / Trust / Verdict（M4-B、M4-C）
- 多家答案餵進同一次 Extractor（Top 10 #2）
- 在 `app/Consensus/` 直接呼叫 Laravel AI SDK（Extractor 經 domain interface + DI）

---

## 完成後交還

1. 更新 [progress.md](progress.md)
2. 建議下一 Gate：**M4-B**
