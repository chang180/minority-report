# Worker Brief — Gate M5-A

**Milestone 5 · Audit Trail + Replay**  
**前置**：M4 全 Gate **RELEASED**  
**狀態**：OPEN

> 合併原規劃 M5-A/B：replay mechanism + §10 欄位完整性驗收。M4-C 已寫入大部分 persistence，本 Gate 聚焦 **可重播** 與 **稽核缺口補齊**。

---

## 角色

Worker Agent。**只做 M5-A**：由 `fixture_id` 或 `request_id` 重播判定鏈；確認 02 §10 audit 欄位完整可還原。

---

## 必讀

1. [docs/07-milestones.md](../../../../docs/07-milestones.md) Milestone 5
2. [docs/02-contracts.md](../../../../docs/02-contracts.md) §10 Audit Trail
3. `ConsensusWorkflow`、現有 models / repositories
4. 本 brief · [progress.md](progress.md)

---

## 交付物

- [ ] **Replay 機制**：依 `request_id`（及/或 fixture registry）重播或還原完整判定鏈
- [ ] **Audit 完整性**：caps / alignment / extraction_status / provider_status 可從 DB 稽核
- [ ] **測試**：單次請求 DB 還原測試；replay 與原 workflow 結果一致（fake/fixture）
- [ ] （若缺）補齊 §10 尚未持久化的欄位

---

## MUST NOT

- 修改 `docs/`、根 `README.md`
- Minimal UI（M6）
- 改動 consensus 算法（除非 replay 發現 bug，寫 progress §4 交 Orchestrator）

---

## 完成後交還

1. 更新 [progress.md](progress.md)
2. M5 milestone 完成後 Orchestrator 標記 **M5 RELEASED**
