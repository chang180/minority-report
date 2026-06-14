# 關鍵報告（Minority Report）— Spec 文件索引

本目錄為 spec-driven 開發的**正式規格書**。決策來源為 [.ai-dev/decisions/description.md](../.ai-dev/decisions/description.md)；若 spec 與 description 在 Laravel 版本上不一致，以 [plan.md](../.ai-dev/planning/plan.md) 的「技術決策更新」為準（**Laravel 13**）。

---

## 開發階段

| 階段 | Milestone | Spec 狀態 | 實作 |
|------|-----------|-----------|------|
| **MVP** | M1–M6 | `docs/00..07` **完成** | **RELEASED**（2026-06-14） |
| **Post-MVP** | M7+ | `docs/08` + `07` §M7 | **RELEASED**（2026-06-14） |
| **M8** | M8-C 可開工 | `docs/09`–`11` + `07` §M8 | M8-A、M8-B **RELEASED** · M8-C **可開工** |

### Spec-driven 規則（M2 起）

1. **M1**：`docs/00..07` 完成前 **MUST NOT** 撰寫 Laravel application code。
2. **M7+**：對應 spec 章節（如 [08-ui-auth-providers.md](08-ui-auth-providers.md)、[09-grounding-and-trust.md](09-grounding-and-trust.md)、[10-product-ux-and-async.md](10-product-ux-and-async.md)、[11-semantic-alignment.md](11-semantic-alignment.md)）與 [07-milestones.md](07-milestones.md) **MUST** 由 Orchestrator 寫入並對齊後，Worker **MAY** 開工該 Gate。
3. Worker **MUST NOT** 直接修改 `docs/`；需求變更寫 progress §4，由 Orchestrator 回寫 spec。
4. 實作 **MUST** 對齊 spec；spec 與程式不一致時，先修 spec 或先修 code，**MUST NOT** 無 spec 漂移合併。

---

## 文件清單與依賴順序

### MVP 核心（M1–M6）

| 文件 | 用途 | 依賴 |
|------|------|------|
| [02-contracts.md](02-contracts.md) | DTO、狀態欄位、Interface 契約 | description §6–11, §18 |
| [03-consensus-algorithm.md](03-consensus-algorithm.md) | 對齊、衝突、Cases、Minority Report | 02 |
| [05-failure-modes.md](05-failure-modes.md) | Provider / Extractor 失敗狀態機 | 02 |
| [04-trust-level.md](04-trust-level.md) | Base + caps 瀑布、decision table | 03 |
| [06-test-scenarios.md](06-test-scenarios.md) | Fixture F01–F14、Success Criteria | 03, 04, 05 |
| [00-product-vision.md](00-product-vision.md) | 願景、MVP 邊界、Non Goals | — |
| [01-architecture.md](01-architecture.md) | 架構、Tech Stack、延遲策略 | 02 |
| [07-milestones.md](07-milestones.md) | Milestone 1–7 拆解與驗收 | 全部 |

### Post-MVP（M7+）

| 文件 | 用途 | 依賴 |
|------|------|------|
| [08-ui-auth-providers.md](08-ui-auth-providers.md) | Auth、UI 路由、per-user provider、Demo 管理 | 00, 01, 02, 07 |
| [09-grounding-and-trust.md](09-grounding-and-trust.md) | Grounding v1、Admin 後端、Trust cap（M8-B） | 02, 04, 07, 08 |
| [10-product-ux-and-async.md](10-product-ux-and-async.md) | 列表、async Job、Email verification、Replay（M8-A） | 07, 08, 09 |
| [11-semantic-alignment.md](11-semantic-alignment.md) | Semantic claim alignment、Admin 設定（M8-C） | 02, 03, 06, 07 |

**M8-C Worker 必讀**：`11-semantic-alignment.md` + [M8-C brief](../.ai-dev/orchestration/briefs/M8-C/brief.md)。

**M8-A Worker 必讀**（已 RELEASED）：`10-product-ux-and-async.md` + [M8-A brief](../.ai-dev/orchestration/briefs/M8-A/brief.md)。

**M8-B Worker 必讀**（已 RELEASED）：`09-grounding-and-trust.md` + [M8-B brief](../.ai-dev/orchestration/briefs/M8-B/brief.md)。

---

## Spec 撰寫規範

所有 agent 撰寫或修訂 spec 時 MUST 遵守：

1. **不得**改寫或弱化 `description.md` 已拍板的 **consensus / trust / audit domain** 決策（T1–T3 等）。
2. Post-MVP 應用層（auth、UI、provider 設定）**MAY** 新增章節，**MUST NOT** 改寫 03–06 算法與 Fixture 語意。
3. **Starter kit**：**MUST** 選擇性移植 Fortify / layout / auth 元件（見 [08 §1.4](08-ui-auth-providers.md)）；**MUST NOT** `laravel new --vue` 整包安裝，以免保留 kit 不需要的 scaffold。
4. 使用 **MUST / MUST NOT / SHOULD / MAY** 語氣（RFC 2119 風格）。
5. 每份 spec 末尾附 **Traceability**。
6. 術語以 [02-contracts.md](02-contracts.md) 為 domain canonical；UI/Auth 術語以 [08-ui-auth-providers.md](08-ui-auth-providers.md) 為 canonical。
7. Tech Stack 寫入 **Laravel 13**、PHP 8.4+。

---

## 術語表（Domain · Canonical）

| 術語 | 定義 |
|------|------|
| `provider_status` | Provider raw answer 呼叫狀態：`success` \| `failed_timeout` \| `provider_unavailable` \| `provider_error` |
| `extraction_status` | Normalized DTO 抽取狀態：`not_started` \| `success` \| `invalid_json` \| `extraction_failed` |
| 可分析 success | `provider_status = success` AND `extraction_status = success` |
| `answer_shape` | `discrete`（離散答案）或 `open`（開放敘述） |
| `direct_answer` | discrete 題：`yes` \| `no` \| `unknown` \| `not_applicable`（open 題專用） |
| `unknown` | discrete 題的棄權；計票時排除，不得觸發 Minority Report |
| `canonical_key` | Extractor 產生的正規化 claim 鍵，供字串對齊 |
| base trust / cap | 見 [04-trust-level.md](04-trust-level.md) |

## 術語表（Application · M7+）

| 術語 | 定義 |
|------|------|
| `consensus slot` | 三個邏輯 provider 名：`openai`、`anthropic`、`gemini`；各槽對應 preset 或 custom 憑證 |
| `preset provider` | 對應 `config/ai.php` 官方 driver 的 user 設定列 |
| `custom provider` | 使用者自訂 label + api_url + api_token |
| `demo mode` | `fake_fixtures` \| `shared_local_api`；Admin 設定 |
| `grounding mode` | `disabled` \| `local_llm_tool_loop` \| `search_api`；Admin 設定（M8-B） |
| `aligner mode` | `string` \| `semantic_llm`；Admin 設定（M8-C） |
| `grounding_available` | Runtime：本次 verification 是否成功取得外部來源（M8-B 前恆 false） |
| `Fortify` | Laravel 13 官方 starter kit 使用的 auth 後端；**本專案 M7 MUST 使用，MUST NOT 使用 Breeze** |
| starter kit 移植 | **選擇性**自 vue-starter-kit 複製所需檔案；**MUST NOT** 整包 `laravel new --vue`（見 08 §1.4） |
| UI 顯示語言 | **僅繁體中文**（API/domain 參數除外）；見 [08 §3.4](08-ui-auth-providers.md) |

---

## 相關非 spec 文件

| 路徑 | 用途 |
|------|------|
| [.ai-dev/orchestration/handoff.md](../.ai-dev/orchestration/handoff.md) | Agent 交接、當前 Gate |
| [.ai-dev/orchestration/briefs/](../.ai-dev/orchestration/briefs/) | Worker 派工（含 MUST NOT 改 docs） |
| [.ai-dev/orchestration/gate-status.md](../.ai-dev/orchestration/gate-status.md) | Gate 放行狀態 |
