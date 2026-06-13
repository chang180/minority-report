# Worker Brief — Gate M4-B

**Milestone 4 · Aligner + Analyzer + Trust Scorer**  
**前置 Gate**：**M4-A RELEASED**  
**狀態**：BLOCKED

---

## 角色

Worker Agent。**只做 M4-B**：Claim Aligner、Consensus Analyzer（Cases 1–6）、Trust Level Scorer（base + caps decision table）。

---

## 必讀

1. [docs/03-consensus-algorithm.md](../../../../docs/03-consensus-algorithm.md)
2. [docs/04-trust-level.md](../../../../docs/04-trust-level.md) §4 decision table
3. [docs/02-contracts.md](../../../../docs/02-contracts.md) §9 對應 interface
4. 本 brief · [progress.md](progress.md)

---

## 交付物

- [ ] `ClaimAligner`、`ConsensusAnalyzer`、`TrustLevelScorer` 實作（替換 Null stubs）
- [ ] Trust decision table 單元測試（含 F13 有效表態==2 列）
- [ ] Cases 1–6 單元測試子集（可不含 Verdict Reporter）

---

## MUST NOT

- Verdict Reporter（M4-C）
- 修改 `docs/`、根 `README.md`
