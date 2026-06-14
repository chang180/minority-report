# 關鍵報告（Minority Report）— Agent Handoff

> **當前階段**：**M8 ✅ 已完成**（M8-A / M8-B / M8-C 全 RELEASED）。  
> **下一階段**：**Post-M8 規劃中** — 候選 **M9**（待 User 拍板主題）· 詳見下方 §Post-M8

「關鍵報告」是一套 Multi-LLM Consensus Engine：降低單一模型幻覺風險，揭露多模型間的共識、分歧與不確定性。

**決策來源**：[decisions/description.md](../decisions/description.md)（共識算法等拍板決策）  
**正式規格**：`docs/00..11`（實作 MUST 以 spec 為準）  
**分階段計畫**：[planning/plan.md](../planning/plan.md) · M8 回顧：[m8-roadmap.md](../planning/m8-roadmap.md)  
**Orchestrator**：[orchestrator.md](orchestrator.md) · Gate：[gate-status.md](gate-status.md) · **派工**：[briefs/](briefs/)

---

## Spec 索引

| 文件 | 摘要 |
|------|------|
| [00-product-vision.md](../../docs/00-product-vision.md) | 願景、哲學、MVP 邊界、Non Goals |
| [01-architecture.md](../../docs/01-architecture.md) | Laravel 13 架構、模組邊界、延遲策略 |
| [02-contracts.md](../../docs/02-contracts.md) | DTO、狀態分離、Interface、Audit 欄位（**術語 canonical**） |
| [03-consensus-algorithm.md](../../docs/03-consensus-algorithm.md) | 對齊、衝突、Cases 1–6、Minority Report |
| [04-trust-level.md](../../docs/04-trust-level.md) | base + caps 瀑布、decision table |
| [05-failure-modes.md](../../docs/05-failure-modes.md) | Provider/Extractor 失敗、3/3–0/3 狀態機 |
| [06-test-scenarios.md](../../docs/06-test-scenarios.md) | Fixture F01–F14、CT-G 測試 |
| [07-milestones.md](../../docs/07-milestones.md) | M1–M8 拆解與驗收 |
| [08-ui-auth-providers.md](../../docs/08-ui-auth-providers.md) | M7 Auth、UI、Provider 憑證 |
| [09-grounding-and-trust.md](../../docs/09-grounding-and-trust.md) | M8-B Grounding、Trust cap |
| [10-product-ux-and-async.md](../../docs/10-product-ux-and-async.md) | M8-A async、Email verification |
| [11-semantic-alignment.md](../../docs/11-semantic-alignment.md) | M8-C Semantic alignment |

---

## 技術決策（優先於 description §6）

| 項目 | 採用 |
|------|------|
| Framework | **Laravel 13** |
| PHP | 8.4+ |
| DB | SQLite（MVP）或 MySQL |

---

## 不可違反的硬性規則 Top 10

1. **MUST NOT** 用單一 LLM Judge 作唯一裁決者。
2. **MUST NOT** 把多家答案餵進同一次 Extractor 呼叫（逐 provider 獨立抽取）。
3. **MUST NOT** 混用 `provider_status` 與 `extraction_status`（禁止含糊 `invalid_response`）。
4. **MUST NOT** 輸出百分比 Trust Score；只用 High/Medium/Low/Unknown + base/caps 瀑布。
5. **MUST NOT** 用 open 題的 `direct_answer` 投票；open 題填 `not_applicable`。
6. **MUST NOT** 把 `direct_answer = unknown` 當反對票或少數意見（棄權，排除計票）。
7. **MUST NOT** 讓 Insufficient（==1）與 Failure（==0）條件重疊。
8. **MUST NOT** 在多軸衝突指向不同 provider 時判 Majority（改判 None）。
9. **MUST NOT** 對 Type C 無 grounding 問題給 High Trust（M8-B 後：grounding success 可放寬部分 cap，見 09）。
10. **MUST NOT** 用 LLM 作 claim value 衝突的唯一裁決者；Evidence「哪方贏」仍屬 Phase 3+（見 §Post-M8）。

---

## 文件編輯權（M2+）

| 路徑 | Worker | Orchestrator |
|------|--------|--------------|
| `docs/`、`docs/README.md` | **MUST NOT** 修改（唯讀） | **唯一**可改動者 |
| 根目錄 `README.md` | **MUST NOT** 修改 | **唯一**可改動者 |
| progress §4 | **MUST** 列「建議 Orchestrator 文件更新」 | 放行前整合進 docs / README |

實作與 spec 不一致、或需補 Development 步驟時，**由 Orchestrator 回寫**，不在 Worker diff 中出現上述路徑。

---

## Milestone 2（Gate 制）

| Gate | 派工文件 |
|------|----------|
| **M2-A**（RELEASED） | [briefs/M2-A/](briefs/M2-A/)（brief + progress） |
| **M2-B**（RELEASED） | [briefs/M2-B/](briefs/M2-B/) |
| **M2-C**（RELEASED） | [briefs/M2-C/](briefs/M2-C/) |
| **M2-D**（RELEASED） | [briefs/M2-D/](briefs/M2-D/) |
| **M2-E**（RELEASED） | [briefs/M2-E/](briefs/M2-E/) |

**M2 已完成。** M2 完成前 MUST NOT 實作 consensus 判定邏輯（屬 M4）。

---

## Milestone 3（Gate 制 · 2 Gate）

| Gate | 派工文件 |
|------|----------|
| **M3-A**（RELEASED） | [briefs/M3-A/](briefs/M3-A/) |
| **M3-B**（RELEASED） | [briefs/M3-B/](briefs/M3-B/) |

**M3 已完成。** M3 完成前 MUST NOT 實作 consensus 判定邏輯；判定邏輯自 **M4** 起。

---

## Milestone 4（Gate 制 · 3 Gate）✅

M4-A … M4-C **RELEASED**（2026-06-13）。Consensus 核心流程（Classifier → Verdict）可跑，F01–F14 通過。

---

## Milestone 5（Gate 制 · 1 Gate）✅

M5-A **RELEASED**（2026-06-14）：`ConsensusReplayService` replay + `auditTrailForRequest`。

---

## Milestone 6（Gate 制 · 1 Gate）✅

M6-A **RELEASED**（2026-06-14）：`/` 問題輸入 + `/verifications/{id}` 結果頁；`ConsensusDemoFixtureCatalog` fake demo。

**M1–M6 MVP 完成。**

---

## Milestone 7（Gate 制 · 2 Gate）— Post-MVP ✅

| Gate | 派工文件 | 狀態 |
|------|----------|------|
| **M7-A** | [briefs/M7-A/](briefs/M7-A/) | **RELEASED** |
| **M7-B** | [briefs/M7-B/](briefs/M7-B/) | **RELEASED** |

**M7 已完成**（2026-06-14）。規格：[docs/08-ui-auth-providers.md](../../docs/08-ui-auth-providers.md)。

---

## Milestone 8（Gate 制 · 3 Gate）— ✅ 已完成

| Gate | 說明 | 狀態 |
|------|------|------|
| **M8-B** | [briefs/M8-B/](briefs/M8-B/) | **RELEASED** |
| **M8-A** | [briefs/M8-A/](briefs/M8-A/) | **RELEASED** |
| **M8-C** | [briefs/M8-C/](briefs/M8-C/) | **RELEASED** |

**M8 已完成**（2026-06-14）。規格：`docs/09`–`11` · [m8-roadmap.md](../planning/m8-roadmap.md)

---

## Post-M8 規劃（2026-06-14 · Orchestrator 草案）

> **狀態**：**尚未立項 M9** — 無 `docs/12`、無 brief。下一輪 **MUST** 由 User 選定主 Milestone 主題後，Orchestrator 撰寫 spec + brief（同 M8 流程）。

### 已交付能力（M1–M8）

| 領域 | Gate | 摘要 |
|------|------|------|
| MVP 共識 + audit + demo UI | M1–M6 | F01–F14、Cases 1–6 |
| 帳號、BYOK、Dashboard | M7 | Fortify、Provider 設定 |
| Grounding v1 | M8-B | Admin 三 mode、Type C Trust 放寬 |
| 產品 UX | M8-A | 列表、async Job、Email verify、Replay |
| 語意 key 對齊 | M8-C | `semantic_llm`、F16、Admin aligner |

測試基線：`php artisan test` → **180+ passed**（2026-06-14）。

### 本機開發備註（非 Milestone · 已實作）

| 項目 | 說明 |
|------|------|
| Email auto-verify | `APP_ENV=local` 或 `AUTH_AUTO_VERIFY_EMAIL=true`：舊帳號 middleware 自動 verified |
| Demo Show | 同步 demo 設 `processing_status=completed`；Show 不對訪客 poll auth status |
| 本機 Provider | Ollama／自訂 endpoint 僅需 URL 即可就緒（`ConsensusSlotReadiness`） |
| Queue | `database` + `queue:listen`；Show 三欄漸進顯示；sync 僅簡單本機、無漸進 UI |
| **provider_options** | `/settings/providers` JSON textarea → `provider_options`（如 `max_tokens`）；`ProviderGenerationOptions` 消毒 |
| **Structured output** | `ConfiguredRawAnswerAgent` 實作 Laravel AI `HasStructuredOutput`；`LaravelAiLlmProvider` 結構化失敗時 fallback 至 `text` |
| **JSON 抽取容錯** | `JsonResponseExtractor`：markdown 區塊、中文 direct_answer、`summary` 推斷、巢狀 payload 解包 |
| **中文 discrete** | `FailSafeQuestionClassifier`：句尾「嗎？」與「是對的嗎」等句式 |
| **API URL** | `ConsensusSlotReadiness::normalizeOpenAiCompatibleBaseUrl()` 剝 `/chat/completions` 後綴；base **SHOULD** 為 `…/v1` |
| **驗證刪除** | 列表單筆／全部刪除；`RunAuthenticatedVerificationJob` 略過已刪除列 |
| **Job 逾時** | `set_time_limit` 於 job 結束 `finally` 還原，避免 worker 空等 crash |
| **本機驗證基線** | 簡短是非題（如「1加1等於2嗎？」「水的沸點…100度嗎？」）→ 抽取 3/3、Full、Trust High（#20–#21） |
| **M8-D 產品文案** | `ConfiguredRawAnswerAgent` / `ProviderPromptBuilder` 強制 summary 繁中；`StructuredVerdictReporter` 繁中 `final_verdict` + partial 缺席註記；`ConsensusDemoFixtureCatalog` 訪客 demo 繁中；`Show.vue` slotState 雙軸判斷 |

### 已知缺口（待 spec）

| 項目 | 說明 | 優先級 |
|------|------|--------|
| **來源／證據信任** | Phase 3：來源可信度、Evidence Comparison；09 §9.2 官方來源 cap 解除 | **高** |
| **Embedding 對齊** | M8-C 僅 `semantic_llm`；`semantic_embedding` 未做 | 中 |
| **生產 Ops** | Queue supervisor、SMTP、deploy runbook、監控 | 中 |
| **UI polish** | Show aligner badge（MAY）、Grounding／對齊 UX 統一 | 低 |
| **真模型 Minority** | Live 驗證 + fixture；grounding + 語意已就緒 | 中 |

**仍排除**（Non Goals）：多租戶、團隊 workspace、付費、完整 RAG、Agent marketplace（[00 §5](../../docs/00-product-vision.md)）。

### M9 候選 Gate（待 User 拍板 · 三選一為主 Milestone）

> **原則**：M9 **MUST NOT** 混太多 Gate；先選一條主線，其餘排 M10+。

#### 候選 A — Evidence & Source Trust（Orchestrator 推薦）

**目標**：在 M8-B Grounding 摘要之上，可稽核地處理**來源分級／證據比對**，可能放寬 Trust cap（對齊 09 官方來源路徑）。

| 可能交付 | 說明 |
|----------|------|
| `docs/12-evidence-and-trust.md` | 新 spec |
| Admin／metadata | 來源 tier、grounding 摘要與 claim 關聯 |
| Trust 最小 diff | 仍 **MUST NOT** LLM 唯一裁決 value 衝突 |

**MUST NOT**：Evidence「哪方贏」的 LLM 單點裁定。

#### 候選 B — Production & Ops

**目標**：可部署、可維運 — 偏工程，少改 consensus domain。

| 可能交付 | 說明 |
|----------|------|
| Deploy / runbook | Queue worker、SMTP、`.env` 生產清單 |
| CI/CD 擴充 | staging smoke、opt-in live tests |
| Health / 監控 | 擴充 `/health`、failed job 告警 |

#### 候選 C — Alignment v2

**目標**：`semantic_embedding` mode、成本／延遲優化、對齊 UX。

| 可能交付 | 說明 |
|----------|------|
| Embedding provider | Admin 可選；fallback 字串 |
| F17+ fixture | embedding 路徑 regression |

### Orchestrator 下一步（Post-M8）

1. User 拍板 M9 主題（A / B / C）
2. 撰寫對應 `docs/12`（或 07 §M9 擴充）+ `m9-roadmap.md`
3. 切 Gate brief → Worker → RELEASED → 更新 [gate-status.md](gate-status.md)

**當前可開工 Gate**：（無）— 見 [briefs/README.md](briefs/README.md)

---

## Agent 工作方式

### 必讀

1. 本文件 + [02-contracts.md](../../docs/02-contracts.md) + 當前 Gate 的 [briefs/](briefs/)
2. Infra：[01-architecture.md](../../docs/01-architecture.md)

### 協作流程（M2+）

1. 使用者依 [briefs/](briefs/) 派 Worker（**brief.md + progress.md**）
2. Worker 實作 → **更新該 Gate 的 progress.md** → 使用者轉交 Orchestrator
3. Orchestrator 審核 progress + 程式碼 → **必要時更新 docs/、根 README** → 更新 [gate-status.md](gate-status.md) → 放行 → 下一 Gate

---

## 文件沿革

| 版本 | 說明 |
|------|------|
| decisions/description.md | spec 前決策 handoff |
| docs/00..07 | M1 正式 spec |
| 本 handoff.md | M2+ 交接 |
| orchestration/briefs/ | Worker 派工 |
