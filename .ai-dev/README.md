# .ai-dev — 開發協作文件

本目錄存放 **決策來源、計畫、Orchestrator 流程與 Worker 派工**，不屬正式對外 spec（對外 spec 在 [`docs/`](../docs/)）。

## 目錄結構

```text
.ai-dev/
├── README.md                 ← 本索引
├── decisions/
│   └── description.md        ← 決策 handoff（spec 前拍板；共識算法等）
├── planning/
│   └── plan.md               ← M1 spec 計畫 + M2+ Gate 摘要
└── orchestration/
    ├── handoff.md            ← 給 Worker / Lead 的精簡交接
    ├── orchestrator.md       ← Lead 審核流程
    ├── gate-status.md        ← Gate 放行狀態（唯一真相）
    └── briefs/               ← 每 Gate 一目錄：brief.md + progress.md
        ├── README.md
        └── M2-A/ … M2-E/
```

## 快速入口

| 你是… | 先讀 |
|--------|------|
| Worker Agent | [briefs/](orchestration/briefs/) 對應 Gate + [handoff.md](orchestration/handoff.md) Top 10；**不**改 `docs/`、根 README |
| Orchestrator（Lead） | [orchestrator.md](orchestration/orchestrator.md) · [gate-status.md](orchestration/gate-status.md) · **`docs/`、根 README 整合** |
| 查決策為何如此 | [decisions/description.md](decisions/description.md) |
| 查正式規格 | [docs/README.md](../docs/README.md) |

## 當前 Gate

**M2-A** — 見 [orchestration/gate-status.md](orchestration/gate-status.md) · 派工 [orchestration/briefs/M2-A/](orchestration/briefs/M2-A/)
