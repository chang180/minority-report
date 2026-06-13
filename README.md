# 關鍵報告 · Minority Report

> **Disagreement is a feature, not a bug.**  
> 多模型交叉驗證引擎——不只要答案，還要共識、分歧、少數意見，以及可稽核的信任等級。

當 OpenAI、Claude、Gemini 對同一問題給出不同說法時，多數系統會悄悄選一個答案糊弄過去。  
**關鍵報告** 的做法相反：保留少數意見、用確定性規則裁決共識，並明確告訴你「這答案有多可信、為什麼不可全信」。

靈感來自電影《Minority Report》——少數預測往往才是關鍵線索。

---

## 這不是什麼

- 不是「三個模型投票，多數贏」的簡化版
- 不是用第四個 LLM 當裁判（避免新的單點幻覺）
- 不是假裝共識等於正確（Consensus ≠ Correctness）

## 這是什麼

一套 **Multi-LLM Consensus Engine**（Laravel 13），完成：

```text
Question → Classification → Multi-Provider Answers → Independent Extraction
    → Claim Alignment → Deterministic Consensus → Trust Level → Verdict Report
```

核心能力（MVP 規格已完稿，實作進行中）：

| 能力 | 說明 |
|------|------|
| **多模型驗證** | 同一問題並行詢問多家 LLM，各自獨立抽取結構化 claims |
| **少數意見報告** | 2 vs 1 分歧時產出 Minority Report，不抹平異議 |
| **信任等級** | `High / Medium / Low / Unknown`，base + caps 瀑布，拒絕假精確百分比 |
| **棄權處理** | `unknown` 是棄權，不是反對票——不會產出「少數意見：我不知道」 |
| **可稽核** | 完整 audit trail，可重播、可 regression test |

---

## 技術棧

| 項目 | 選型 |
|------|------|
| Framework | **Laravel 13** |
| PHP | 8.4+ |
| Database | SQLite（MVP）/ MySQL |
| AI Infrastructure | Laravel AI SDK（介面層；domain 不綁 vendor） |
| Providers | OpenAI · Anthropic · Gemini + **fake provider**（測試一等公民） |

---

## 專案狀態

| Milestone | 狀態 |
|-----------|------|
| M1 Spec Documents | ✅ 完成 |
| M2 Laravel Skeleton | 🔜 下一步 |
| M3 Provider Integration | 待開始 |
| M4 Consensus Engine | 待開始 |
| M5 Audit Trail | 待開始 |
| M6 Minimal UI | 待開始 |

目前 repo **尚無 application code**；所有行為以 spec 為準，進入 M2 後才開始撰寫 Laravel 程式碼。

---

## 文件

| 路徑 | 內容 |
|------|------|
| [docs/README.md](docs/README.md) | Spec 索引與術語表 |
| [docs/00-product-vision.md](docs/00-product-vision.md) | 產品願景與 MVP 邊界 |
| [docs/01-architecture.md](docs/01-architecture.md) | 架構與模組邊界 |
| [docs/02-contracts.md](docs/02-contracts.md) | DTO、Interface、Audit 契約 |
| [docs/03-consensus-algorithm.md](docs/03-consensus-algorithm.md) | 共識演算法 Cases 1–6 |
| [docs/04-trust-level.md](docs/04-trust-level.md) | Trust base + caps 瀑布 |
| [docs/05-failure-modes.md](docs/05-failure-modes.md) | 失敗模式狀態機 |
| [docs/06-test-scenarios.md](docs/06-test-scenarios.md) | Fixture F01–F14、CT-G 測試 |
| [docs/07-milestones.md](docs/07-milestones.md) | 開發里程碑 |

開發者交接：[.ai-dev/handoff.md](.ai-dev/handoff.md)

---

## 開發

```bash
# M2 起（尚未初始化）
composer create-project laravel/laravel . "^13.0"
cp .env.example .env
php artisan key:generate
```

貢獻或協作前請先閱讀 `docs/02-contracts.md` 與 `.ai-dev/handoff.md` 中的 **Top 10 硬性規則**。

---

## License

[MIT License](LICENSE) — 可自由使用、修改與散布；軟體按「現狀」提供，不提供任何明示或默示擔保。
