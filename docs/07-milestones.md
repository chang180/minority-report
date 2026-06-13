# 07 — Milestones（開發里程碑）

本文件將 MVP 拆解為 Milestone 1–6，定義進入條件、交付物與驗收方式。願景見 [00-product-vision.md](00-product-vision.md)；架構見 [01-architecture.md](01-architecture.md)。

---

## 概覽

| Milestone | 名稱 | 狀態 |
|-----------|------|------|
| M1 | Spec Documents | **完成**（本 repo `docs/00..07`） |
| M2 | Laravel Skeleton | **完成**（2026-06-13） |
| M3 | Provider Integration | **完成**（2026-06-13） |
| M4 | Consensus Engine | **進行中**（M4-A ✅；M4-B 下一步） |
| M5 | Audit Trail | 待開始 |
| M6 | Minimal UI | 待開始 |

**M1 完成前 MUST NOT 撰寫 Laravel application code。** M2 起可開始實作。

---

## Milestone 1: Spec Documents

### 進入條件

- `.ai-dev/decisions/description.md` 決策文件存在

### 交付物

- [x] `docs/00-product-vision.md`
- [x] `docs/01-architecture.md`
- [x] `docs/02-contracts.md`
- [x] `docs/03-consensus-algorithm.md`
- [x] `docs/04-trust-level.md`
- [x] `docs/05-failure-modes.md`
- [x] `docs/06-test-scenarios.md`
- [x] `docs/07-milestones.md`
- [x] `.ai-dev/planning/plan.md`
- [x] `.ai-dev/orchestration/handoff.md`

### 驗收

- 全 spec 交叉審核通過（見 §Cross-Review）
- 術語與 `description.md` T1–T3 決策一致
- Laravel 13 技術決策已記錄

---

## Milestone 2: Laravel Skeleton

### 進入條件

- M1 完成
- `.ai-dev/orchestration/handoff.md` 已讀

### 交付物

- Laravel **13** 專案初始化
- 基本 routes、config、`.env.example`
- migration / model skeleton（audit 用）
- `app/Consensus/` 目錄骨架 + 空 interface 實作占位
- Service provider 組裝 wiring

### 驗收

- `php artisan` 可執行
- domain 目錄符合 [01-architecture.md](01-architecture.md) §2
- **MUST NOT** 在此 milestone 完成 consensus 邏輯

### 實作順序

1. `composer create-project laravel/laravel`（v13）
2. 建立 `app/Consensus/*` 骨架
3. 建立 `app/AI/Providers/` 占位
4. 定義 config `consensus.php`（number conflict 5% 等）

---

## Milestone 3: Provider Integration

### 進入條件

- M2 完成

### 交付物

- `LlmProvider` domain 契約實作（含 fake provider）
- `app/AI/Providers/*`：Laravel AI SDK adapter，bridge 至 domain interface
- OpenAI / Claude / Gemini backend（可分批；未就緒者以 fake 頂替）
- 並行 provider 查詢
- per-provider raw answer 持久化

### 驗收

- fake provider 可 replay F01
- 單 provider 失敗不中断 pipeline
- timeout 重試至多一次

### 優先順序

**fake provider 優先**——consensus logic 必須先被 fixtures 驗證，再接真 API。

---

## Milestone 4: Consensus Engine

### 進入條件

- M3 fake provider 可用

### 交付物

- Question Classifier（含 fail-safe bias；**CT-G1–G3** 單元測試，見 [06 §4](06-test-scenarios.md)）
- Response Extractor（**逐 provider 獨立**）
- Claim Aligner
- Consensus Analyzer（Cases 1–6）
- Trust Level Scorer（base + caps；**§2 雙計數 cap 表** decision table 單元測試）
- Verdict Reporter（LLM-assisted，non-binding）
- PHPUnit：Fixture F01–F14

### 驗收

- [06-test-scenarios.md](06-test-scenarios.md) F01–F14 + **CT-G1–G3** 通過
- [04-trust-level.md](04-trust-level.md) §4 decision table（含 F13 有效表態==2 列）通過
- T1–T3 交叉審核項通過

---

## Milestone 5: Audit Trail

### 進入條件

- M4 核心流程可跑

### 交付物

- request / provider / extraction / consensus result 記錄
- [02-contracts.md](02-contracts.md) §10 欄位完整持久化
- replay mechanism（由 fixture_id 或 request_id 重播）

### 驗收

- 單次請求可從 DB 還原完整判定鏈
- caps / alignment / extraction_status 可稽核

---

## Milestone 6: Minimal UI

### 進入條件

- M4 + M5 完成

### 交付物

- 問題輸入頁
- 驗證結果頁（consensus、trust、minority report）
- provider 回應比對檢視
- final verdict 顯示

### 驗收

- 可透過 UI 跑 fake fixture demo
- Type A 走單模型短路徑

---

## Phase 3 延後項目（Non-MVP）

以下 **MUST NOT** 在 M1–M6 引入：

- Web Search / Fact Check grounding
- semantic claim alignment（embedding / LLM）
- 來源可信度裁定
- Evidence Comparison 勝負裁定
- 完整 RAG
- Job polling UI（除非 latency 觸發 §01 5.2）

---

## Cross-Review 檢查清單（M1）

| ID | 檢查項 | 文件 | 狀態 |
|----|--------|------|------|
| T1-A | 逐 provider 獨立抽取 | 02, 03 | ✓ |
| T1-B | claim 對齊 + 衝突判準 | 03 | ✓ |
| T2-D | base + caps 瀑布 | 04 | ✓ |
| T2-F | answer_shape discrete/open | 02, 03 | ✓ |
| T2-G | Classifier fail-safe bias（CT-G1–G3） | 02 §2.4, 06 §4 | ✓ |
| T3-K | provider/extraction 分離 | 02, 05 | ✓ |
| T3-M | Insufficient vs Failure | 03, 05 | ✓ |
| T3-N | 多軸衝突 → None | 03, 06-F14 | ✓ |
| T3-O | unknown 棄權 | 02, 03, 06-F13 | ✓ |

---

## Traceability

| 本文件章節 | description.md |
|------------|----------------|
| M1–M6 | §21 |
| Cross-Review | §23, Changelog T1–T3 |
| Phase 3 延後 | §5, §14, §12.2 |

**技術決策覆寫**：M2 使用 Laravel 13，見 [.ai-dev/planning/plan.md](../.ai-dev/planning/plan.md)。
