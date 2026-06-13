# Worker Brief — Gate M2-B

**Milestone 2 · Consensus / AI 目錄與 Interface 骨架**  
**前置 Gate**：M2-A **RELEASED**  
**狀態**：**RELEASED**（2026-06-13）

---

## 角色

Worker Agent。**只做 M2-B**：目錄 + interface + 空 DTO class，**不含方法實作**。

---

## 必讀

1. [handoff.md](../handoff.md) Top 10
2. [docs/02-contracts.md](../../../../docs/02-contracts.md) §9 PHP Interface 草案
3. [docs/01-architecture.md](../../../../docs/01-architecture.md) §2 目錄結構
4. 本 brief · [progress.md](progress.md)（**完成後 MUST 更新**）

---

## 交付物

### 目錄（須與 01-architecture 一致）

```text
app/Consensus/
├── Contracts/
├── DTO/
├── Classifier/
├── Extractor/
├── Aligner/
├── Analyzer/
├── Scorer/
└── Reporter/

app/AI/
└── Providers/
```

### Interface（`app/Consensus/Contracts/`）

依 **02 §9** 建立 PHP interface（簽名一致，方法體不在 interface 內）：

- `QuestionClassifier`
- `LlmProvider`
- `ResponseExtractor`
- `ClaimAligner`
- `ConsensusAnalyzer`
- `TrustLevelScorer`
- `VerdictReporter`
- `FakeProviderRegistry`（可放 Contracts 或 AI 層，但須有 interface）

### DTO 骨架（`app/Consensus/DTO/`）

空 class 或最小 property 占位，**MUST** 能對應 02 §2–§3 schema 命名，例如：

- `Question`
- `ClassificationResult`
- `ProviderResponse`
- `ConsensusResult`
- `TrustLevelResult`
- `VerdictReport`
- `AlignmentResult`
- `AnalysisContext`
- `VerdictInput`

（可增 supporting DTO；**MUST NOT** 實作 consensus 判定邏輯。）

### PSR-4

`composer.json` autoload 已涵蓋 `App\`（Laravel 預設即可）。

---

## MUST NOT

- 修改 `docs/`、根目錄 `README.md`（Orchestrator 專責；需求寫 progress §4）
- 實作任何 consensus / trust / align 算法
- **呼叫** LLM 或 Laravel AI SDK（含在 `app/Consensus/` 或 `app/AI/Providers/` 寫 adapter 實作）
- 撰寫 migration（**M2-D**；`laravel/ai` 的 agent conversation 表已在 **M2-A**）
- 撰寫 `config/consensus.php` 業務綁定（**M2-C**）

> **`laravel/ai` 已在 M2-A 安裝**；本 Gate 僅建立 interface / DTO 骨架，不碰 SDK。

---

## 驗收

```bash
composer dump-autoload
php -r "require 'vendor/autoload.php'; interface_exists(App\\Consensus\\Contracts\\LlmProvider::class) || exit(1); echo 'ok';"
php artisan --version
```

可選：空 PHPUnit smoke test 僅 assert interface 存在。

---

## 完成後交還

1. 更新 [progress.md](progress.md)
2. 與 02 §9 對照表
3. 留給 M2-C
