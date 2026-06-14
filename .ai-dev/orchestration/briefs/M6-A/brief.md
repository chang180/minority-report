# Worker Brief — Gate M6-A

**Milestone 6 · Minimal UI（問題輸入 + 驗證結果）**  
**前置**：M5 全 Gate **RELEASED**  
**狀態**：OPEN

> 合併原規劃 M6-A/B：問題提交頁 + 結果頁（consensus、trust、minority report、provider 比對）一次交付。

---

## 角色

Worker Agent。**只做 M6-A**：Vue + Inertia 最小 UI，串接 `ConsensusWorkflow`（可先用 fake provider / fixture demo）。

---

## 必讀

1. [docs/07-milestones.md](../../../../docs/07-milestones.md) Milestone 6
2. [docs/00-product-vision.md](../../../../docs/00-product-vision.md) MVP UI 邊界
3. `ConsensusWorkflow`、`ConsensusReplayService`
4. 本 brief · [progress.md](progress.md)

---

## 交付物

- [ ] **問題輸入頁**：提交 question，觸發 verification workflow
- [ ] **結果頁**：顯示 consensus status、trust level、verdict / minority report
- [ ] **Provider 比對檢視**：各 provider raw/extracted 摘要
- [ ] fake fixture demo 可從 UI 跑通（不需真 API key）
- [ ] Feature test（Inertia 頁面或 HTTP 流程）

---

## MUST NOT

- 修改 `docs/`、根 `README.md`（需求寫 progress §4）
- Phase 3 功能（grounding、semantic alignment、RAG）
- 改動 consensus 算法（屬 domain，非 UI Gate）

---

## 完成後交還

1. 更新 [progress.md](progress.md)
2. M6 milestone 完成 → Orchestrator 標記 **M6 RELEASED**
