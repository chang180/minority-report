# Worker Brief — Gate M4-C

**Milestone 4 · Verdict Reporter + Fixture 整合驗收**  
**前置 Gate**：**M4-B RELEASED**  
**狀態**：**RELEASED**（2026-06-13）

---

## 角色

Worker Agent。**只做 M4-C**：Verdict Reporter + **F01–F14** 與 **CT-G** 全量驗收；M4 milestone 完成。

---

## 必讀

1. [docs/06-test-scenarios.md](../../../../docs/06-test-scenarios.md) F01–F14
2. [docs/07-milestones.md](../../../../docs/07-milestones.md) M4 驗收
3. 本 brief · [progress.md](progress.md)

---

## 交付物

- [ ] `VerdictReporter` 實作（LLM-assisted，non-binding）
- [ ] 端到端流程：Classifier → providers → Extractor → Aligner → Analyzer → Trust → Verdict
- [ ] **F01–F14** + **CT-G1–G3** 全通過
- [ ] `consensus_results` 持久化

---

## MUST NOT

- 修改 `docs/`、根 `README.md`
- Minimal UI（M6）
