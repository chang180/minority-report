# 07 — Milestones（開發里程碑）

本文件將 MVP 拆解為 Milestone 1–6，定義進入條件、交付物與驗收方式。願景見 [00-product-vision.md](00-product-vision.md)；架構見 [01-architecture.md](01-architecture.md)。

---

## 概覽

| Milestone | 名稱 | 狀態 |
|-----------|------|------|
| M1 | Spec Documents | **完成**（本 repo `docs/00..07`） |
| M2 | Laravel Skeleton | **完成**（2026-06-13） |
| M3 | Provider Integration | **完成**（2026-06-13） |
| M4 | Consensus Engine | **完成**（2026-06-13） |
| M5 | Audit Trail | **完成**（2026-06-14） |
| M6 | Minimal UI | **完成**（2026-06-14） |
| M7 | Product UI + Auth | **完成**（2026-06-14） |
| M8 | UX 成熟 + Grounding + Semantic | **進行中**（M8-B 可開工） |

**M1 完成前 MUST NOT 撰寫 Laravel application code。** M2 起可開始實作。  
**M7 開工前 MUST** 先完成 [08-ui-auth-providers.md](08-ui-auth-providers.md) 與本文件 §M7（spec-driven）。  
**M8-B 開工前 MUST** 先完成 `docs/09-grounding-and-trust.md`（規劃見 [.ai-dev/planning/m8-roadmap.md](../.ai-dev/planning/m8-roadmap.md)）。

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

**備註**：M6 為 **Minimal UI**（workflow 閉環證明），非產品級前端。完整 IA、Auth、Dashboard 見 **M7** 與 [08-ui-auth-providers.md](08-ui-auth-providers.md)。

---

## Milestone 7: Product UI + Auth + Provider Settings（Post-MVP）

### 進入條件

- M6 **RELEASED**
- [08-ui-auth-providers.md](08-ui-auth-providers.md) 已由 Orchestrator 寫入並對齊

### 概覽

| Gate | 名稱 | 狀態 |
|------|------|------|
| M7-A | Fortify + Vue kit 基礎 + Welcome + Demo 路由 | **RELEASED** |
| M7-B | Provider 設定 + Admin demo + Dashboard + 真 verification | **RELEASED** |

詳細規格 **MUST** 以 [08-ui-auth-providers.md](08-ui-auth-providers.md) 為準。

### M7-A 交付物

- Laravel **Fortify**（**MUST NOT** 使用 Breeze）
- vue-starter-kit **選擇性移植**（**MUST NOT** 整包 `laravel new --vue`；見 08 §1.4）：layouts、auth 頁、settings（Profile/Password）
- `users.role`（`admin` \| `user`）；admin middleware
- Welcome `GET /`；Demo 遷至 `GET /demo/*`
- Feature test：`M7AAuthTest`；更新 `M6MinimalUiTest`

### M7-A 驗收

- 開放註冊 + login/logout + password reset
- `/` Welcome；`/demo` 保留 M6 fake fixture 行為
- **使用者可見 UI 為繁體中文**（[08 §3.4](08-ui-auth-providers.md)）
- `npm run typecheck`；全 suite 綠

### M7-B 交付物

- `user_provider_settings`、`user_custom_providers`、`system_demo_settings`
- `users.consensus_slots`；`ConfiguredLlmProviderFactory::forUser()` / `forDemo()`
- Provider 設定 UI；Admin demo 管理；產品 Dashboard
- 登入使用者 verification（`user_id`、policy）
- Feature tests：M7B*

### M7-B 驗收

- 使用者可設定 SDK preset + 自訂 endpoint
- Admin 可切換 demo mode（fake / shared local API）
- 登入 verification 使用 per-user provider；audit 含 `user_id`，**不含** secret
- 詳見 [08-ui-auth-providers.md §7](08-ui-auth-providers.md)

---

## Milestone 8: 產品成熟 + Grounding（進行中）

> 規格：[09-grounding-and-trust.md](09-grounding-and-trust.md) · 規劃：[m8-roadmap.md](../.ai-dev/planning/m8-roadmap.md)

### 概覽

| Gate | 名稱 | 狀態 |
|------|------|------|
| M8-B | Grounding v1 + trust cap + Admin 設定 | **RELEASED** |
| M8-A | Verification 列表 + async + Email verification | **RELEASED** |
| M8-C | Semantic claim alignment | 規劃中 |

### M8-B 進入條件

- M7 **RELEASED**
- [09-grounding-and-trust.md](09-grounding-and-trust.md) **已發布**（2026-06-14）

### M8-B 交付物

- `system_grounding_settings`；Admin `/admin/grounding`
- `app/Grounding/` — `GroundingService` + 三 mode strategy
- `local_llm_tool_loop`（`web_search` tool loop）+ `search_api`
- Auth/demo verification 注入 grounding metadata
- Trust cap 調整（Type C + grounding success）；F15
- `M8BGroundingTest`；Show.vue grounding 區塊（繁中）

### M8-B 驗收

- 詳見 [09-grounding-and-trust.md §11](09-grounding-and-trust.md)

### M8-A / M8-C（概要）

### M8-A 進入條件

- M8-B **RELEASED**
- [10-product-ux-and-async.md](10-product-ux-and-async.md) **已發布**（2026-06-14）

### M8-A 交付物

- `processing_status` + `RunAuthenticatedVerificationJob`
- `GET /verifications` 列表；Show polling；`GET .../status`
- Fortify Email verification（本機 auto-verify）
- `POST .../replay` + UI
- `M8A*` tests

### M8-A 驗收

- 詳見 [10-product-ux-and-async.md §10](10-product-ux-and-async.md)

### M8-C（概要）

- Semantic aligner；更新 03 / 06

---

## Phase 3 延後項目（Non-MVP · M1–M7）

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
| M7 | [08-ui-auth-providers.md](08-ui-auth-providers.md)、Orchestrator M7 brief |
| Cross-Review | §23, Changelog T1–T3 |
| Phase 3 延後 | §5, §14, §12.2 |

**技術決策覆寫**：M2 使用 Laravel 13，見 [.ai-dev/planning/plan.md](../.ai-dev/planning/plan.md)。
