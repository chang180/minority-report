# 01 — Architecture（系統架構）

本文件定義技術堆疊、模組邊界、工作流程與延遲策略。契約見 [02-contracts.md](02-contracts.md)。

---

## 1. Tech Stack

| 項目 | 選型 | 備註 |
|------|------|------|
| Framework | **Laravel 13** | 覆寫 description.md §6 的 Laravel 12+ |
| PHP | 8.4+ | |
| Database | MySQL 或 SQLite | MVP 可先用 SQLite |
| Frontend | **Vue 3** + **Inertia.js** + **TypeScript** + Tailwind CSS 4 | M6 Minimal UI；頁面於 `resources/js/Pages/` |
| Testing | **Pest** | TDD；CI 於 push/PR 執行 |
| AI Infrastructure | **Laravel AI SDK**（`laravel/ai`） | 已安裝；MVP bridge 限 `app/AI/`；Consensus **MUST NOT** 直接依賴 SDK facade |
| AI 開發規範 | **Laravel Boost** | guidelines / skills / MCP；含 `ai-sdk-development` |

---

## 2. Architecture Principle

**Clean boundary, not premature portability.**

MVP 不需一開始支援 Web、Discord、MCP、Claude Code Skill、Cursor Agent 等多種 surface，但核心邏輯 **MUST** 放在乾淨的 domain layer，避免 Laravel infrastructure 汙染核心邏輯。

### 2.1 目錄結構

```text
app/
├── Consensus/
│   ├── Contracts/
│   ├── DTO/
│   ├── Classifier/
│   ├── Extractor/      ← 逐 provider 獨立抽取
│   ├── Aligner/
│   ├── Analyzer/
│   ├── Scorer/
│   └── Reporter/
│
├── AI/
│   └── Providers/
│
├── Http/
├── Models/
└── Jobs/
```

### 2.2 依賴規則

| 規則 | 說明 |
|------|------|
| `app/Consensus` **MUST NOT** 直接依賴 Eloquent | 透過 repository interface 注入 |
| `app/Consensus` **MUST NOT** 直接依賴 Queue | Job 在 Laravel 層 dispatch |
| `app/Consensus` **MUST NOT** 直接依賴 Laravel AI SDK facade | 透過 `LlmProvider` 等 domain interface；SDK 只在 `app/AI/` |
| 外部服務 **MUST** 透過 interface 注入 | 見 02 §9 |
| Laravel **ONLY** 負責 HTTP、DB、Queue、設定與組裝 | |

---

## 3. System Workflow

```text
User Question
        │
        ▼
Question Classifier  (fail-safe bias: C > B > A)
        │
        ▼
Question Type + answer_shape
        │
 ┌──────┼────────────┐
 │      │            │
 A      B            C
 │      │            │
 ▼      ▼            ▼
Single  Multi-LLM    Multi-LLM + Requires Grounding
Answer  Consensus    (MVP: grounding_available=false → Trust cap)
        │            │
        └─────┬──────┘
              ▼
   Provider raw answers (parallel)
              ▼
   Per-provider independent extraction (parallel)
              ▼
   Cross-provider claim alignment
              ▼
   Deterministic comparison + rule-based classification
              ▼
   Trust Level (base + caps)
              ▼
   Verdict Reporter (LLM-assisted; non-binding)
```

Type A 在 Classifier 後直接單模型回答，不進入 consensus 子流程。

---

## 4. AI Layer（介面化，非選型競賽）

本專案在 AI 基礎設施層的目標是 **把 AI 呼叫 interface 化**，讓 `app/Consensus` 只依賴 domain 契約（見 [02-contracts.md §9](02-contracts.md)），不綁死特定 vendor 或 SDK 細節。

### 4.1 分層

```text
app/Consensus/          ← domain：LlmProvider、Classifier、Extractor 等契約
        │
        ▼
app/AI/                 ← infrastructure：adapter / bridge
        │
        ▼
Laravel AI SDK          ← Laravel 13 官方 AI 介面層（MVP 採用）
        │
        ▼
OpenAI / Anthropic / Gemini / fake …   ← 具體 backend（可替換、可擴充）
```

- **MVP 採用 Laravel AI SDK** 作為 AI infrastructure，負責把各家 API **統一成可注入的介面**，而不是在 domain 內自行寫 HTTP client。
- Domain 的 `LlmProvider` **MUST** 由 `app/AI/` 的 adapter 實作；Consensus 程式碼 **MUST NOT** 直接呼叫 Laravel AI SDK facade。
- 後續是否支援 Ollama、OpenRouter、本地模型等，**SHOULD** 透過新增 adapter 解決，**MUST NOT** 改動 consensus 核心邏輯。

### 4.2 實作要求（非 PoC 阻塞）

| 項目 | 要求 |
|------|------|
| 介面邊界 | `app/Consensus` 只認 [02 §9](02-contracts.md) 契約 |
| Infrastructure | `app/AI/Providers/*` 橋接 Laravel AI SDK |
| timeout / 重試 | 在 adapter 或 config 設定；timeout 至多重試一次 |
| 並行 | **MUST** 支援 3 provider 並行查詢 |
| fake / testing | fake provider **MUST** 實作同一 `LlmProvider` 契約，不依賴 SDK |

**MUST NOT** 把「SDK 選型 PoC」「Prism 比較」當作 Milestone 阻塞條件。Prism 等僅作為 Laravel AI SDK 不可用時的後備參考，不在 MVP 必做範圍。

### 4.3 MVP 目標 Provider

MVP **SHOULD** 支援三個遠端 backend：OpenAI、Anthropic Claude、Google Gemini（經 Laravel AI SDK 或等價 adapter）。  
若某家 SDK 尚未就緒，**MAY** 以 fake provider + 部分 adapter 先行；**MUST NOT** 因此改寫 domain 契約。

fake provider **MUST** 為測試一等公民。
---

## 5. Latency Strategy

單次 Type B/C 同步路徑估算（逐 provider 獨立抽取）：

| 階段 | 呼叫數 | 預估 | 並行化 |
|------|--------|------|--------|
| 分類 | 1 | ~1–3s | — |
| Provider 查詢 | 3 | ~3–10s | **MUST** 並行 |
| 獨立抽取 | 3 | ~3–8s | **SHOULD** 並行 |
| 報告生成 | 1 | ~3–8s | — |

並行化後同步路徑約 **10–25s**，尖峰可能 **> 30s**。

### 5.1 MVP 決策

- MVP **SHOULD** 採同步流程（降低複雜度、方便本地測試）。
- Provider 查詢與抽取 **MUST** 並行，否則易超過 30s。
- MVP **MUST NOT** 先實作完整 job polling UI。

### 5.2 Phase 2 升級觸發

若實測單次 > 30s 成常態：

```text
Job dispatch → Polling → Result page
```

---

## 6. External References（概念借鏡）

可參考方向：LLM Council、Duh、Star Chamber、Multi LLM Cross Check、ReConcile。

- **ONLY** 借鏡流程設計、分類策略、共識分級、少數意見保留、收斂與失敗模式概念。
- **MUST NOT** 直接複製受限制授權專案程式碼。

建議精讀：

- Duh：domain-capped confidence 與收斂偵測
- Star Chamber：各 provider 獨立審查、結構化 JSON、依共識分級

---

## Traceability

| 本文件章節 | description.md |
|------------|----------------|
| §1 Tech Stack | §6（Laravel 13 覆寫見 plan.md） |
| §2 架構原則 | §7 |
| §3 Workflow | §8 |
| §4 AI Layer | §6, §10 |
| §5 Latency | §17, T3-J |
| §6 References | §20 |

**技術決策覆寫**：Framework Laravel 13 見 [.ai-dev/planning/plan.md](../.ai-dev/planning/plan.md)。AI infrastructure 採 Laravel AI SDK 作介面層，非 SDK 選型競賽。
