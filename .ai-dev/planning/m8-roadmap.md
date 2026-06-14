# Milestone 8 規劃草案（Orchestrator · 2026-06-14）

> **狀態**：M8-B **RELEASED** · M8-A **可開工** · spec **已發布**（2026-06-14）  
> **前置**：M7 **RELEASED**  
> **原則**：spec-driven — 各 Gate 開工前 **MUST** 有對應 `docs/` 章節（新文件或 07 §M8 擴充）

---

## 1. 為什麼需要 M8

M7 完成後，產品已具備 **BYOK + 真 verification 路徑**，但仍有結構性缺口：

| 缺口 | 現況 | 影響 |
|------|------|------|
| **Grounding 永遠 false** | Type C 題 Trust 被 cap 在 Low/Unknown | 無法對時效性問題給出有意義的高信任路徑 |
| **字串 aligner** | `StringClaimAligner` 無法合併措辭不同的同義 claim | 真模型易 false negative → No Consensus |
| **Verification UX** | 僅 create/show；無列表、無 async | 三 provider 同步跑可能 timeout；難回顧歷史 |
| **真模型 Minority** | 00 §3.2：真 API 幾乎不觸發 minority | Demo 仍靠 fixture；產品需 grounding 才成熟 |

M8 **不**做：多租戶、團隊 workspace、付費、完整 RAG pipeline、Agent marketplace（延續 Non Goals）。

---

## 2. 建議 Gate 切分

```text
M8-A  產品 UX 完成度     （應用層 · 不改 consensus 算法）
M8-B  Grounding v1       （adapter + metadata + trust cap · 需新 spec）
M8-C  Semantic alignment  （aligner 替換/擴充 · 需新 spec · 可與 B 並行 spec）
```

### M8-A — 產品 UX 完成度（優先開工候選）

**目標**：讓 M7 路徑「日常可用」，不碰 domain 判定。

| 交付物 | 說明 |
|--------|------|
| Verification 列表 | `GET /verifications` — 本人紀錄；admin 可選全部 |
| Async workflow | `POST /verifications` dispatch Job；結果頁 polling 或 Inertia deferred |
| 狀態 UX | pending / running / completed / failed；繁中 |
| Replay 入口 | Dashboard / Show 連至 `ConsensusReplayService` 重跑（UI 層） |
| Email verification | Fortify **MUST** 啟用；見 §4 D1（本機自動通過 / 生產需驗證） |
| 測試 | `M8AVerificationListTest`、`M8AAsyncVerificationTest` |

**MUST NOT**：改 Trust 算法、改 aligner、引入 grounding fetch。

**Spec 需求**：Orchestrator 擴充 `docs/07-milestones.md §M8-A` + 可選 `docs/09-product-ux.md`（輕量）。

---

### M8-B — Grounding v1

**目標**：對 Type C / `requires_grounding` 問題，**可選**取得外部來源，將 `grounding_available` 設為 true，並依 [04-trust-level.md](../../docs/04-trust-level.md) 放寬 **部分** cap（**MUST NOT** 無驗證直接 High）。

| 交付物 | 說明 |
|--------|------|
| Grounding adapter | `app/Grounding/` — **Admin 可設定** grounding 後端（見 §4 D2） |
| Admin 設定 UI | `GET/PUT /admin/grounding`（或併入既有 admin 區）— mode、API URL、search provider |
| Question metadata | workflow 前注入 `grounding_available`、sources 摘要（**不含** API key） |
| Trust 整合 | `CascadeTrustLevelScorer` 讀取真實 grounding 狀態（**最小** diff） |
| Fixture 策略 | 新增 F15+ 或 mock grounding；**MUST NOT** 破壞 F01–F14 |
| 測試 | `M8BGroundingTest`、Trust cap regression |

**MUST NOT**（延續 00 §5、07 Phase 3 節錄）：

- 完整 RAG / 向量庫
- Evidence Comparison「哪方證據贏」的 LLM 裁定
- 在 `app/Consensus/` 直接呼叫 HTTP / Search SDK（走注入 metadata 或 port）

**Spec 需求**：**MUST** 新增 `docs/09-grounding-and-trust.md`（契約、失敗模式、trust 調整表、audit 欄位）後才能發 brief。

---

### M8-C — Semantic claim alignment

**目標**：在 aligner 層支援語意等價（embedding 或 LLM），減少真模型 No Consensus。

| 交付物 | 說明 |
|--------|------|
| `SemanticClaimAligner` | 實作 `ClaimAligner` contract；可 feature-flag |
| F08 釐清 | 測試語意對齊路徑；保留字串 aligner 作 fallback |
| 性能 / 成本 | 僅對 analyzable responses 執行；timeout 策略 |

**Spec 需求**：`docs/09` 或獨立 `docs/10-semantic-alignment.md`；**MUST** 更新 03 §對齊、06 fixture 備註。

**依賴**：可與 M8-B **spec 並行撰寫**；**實作**建議在 M8-B 之後（grounding 優先解 Trust 天花板）。

---

## 3. 建議開工順序

> **User 優先**：Grounding（M8-B）為核心；M8-A 可並行或緊接在後。

```text
1. Orchestrator 撰寫 docs/09-grounding-and-trust.md（M8-B spec · 含 Admin 可設定後端）
2. 發布 M8-B brief → Worker → RELEASED
3. Orchestrator 擴充 docs/07 §M8-A；發布 M8-A brief（含 Email verification）
4. 發布 M8-A → RELEASED
5. 撰寫 semantic spec → M8-C brief → RELEASED
```

---

## 4. 已拍板決策（2026-06-14 · User）

| # | 決策 | 規格摘要 |
|---|------|----------|
| **D1** | **含 Email verification** | M8-A 啟用 Fortify Email Verification。**本機**（`APP_ENV=local` 或 `MAIL_MAILER=log`）：註冊後 **自動標記 `email_verified_at`**（或等價 bypass middleware），開發不中斷。**生產**：須設定 SMTP/SES 等；使用者 **MUST** 完成驗證信流程後才能使用 verification 等受保護功能。 |
| **D2** | **Admin 可設定 Grounding 後端（多選項）** | 非寫死本機模型。`system_grounding_settings`（或擴充既有 singleton）由 **admin** 設定 `mode`：<br>• `local_llm_tool_loop` — `LOCAL_AI_API_URL` + `web_search` tool loop（**目前 dev 預設**）<br>• `search_api` — Tavily / Serper / 等（API key encrypted）<br>• `disabled` — 等同 MVP（`grounding_available=false`）<br>Worker 實作 **Strategy 介面**；切換後端不改 `app/Consensus/`。 |
| **D3** | **A · Async 必做** | M8-A：`POST /verifications` dispatch Job；結果頁 polling / deferred。 |
| **D4** | **A · M8-C 留在 M8** | Semantic alignment 不拆 M9；spec 可與 M8-B 並行撰寫。 |

### D2 實測摘要（dev · `LOCAL_AI_API_URL=http://localhost:8080`）

| 項目 | 結果 |
|------|------|
| `/v1/models` | ✓ `gemma-4-E2B_q4_0-it.gguf` |
| `web_search` tool calling | ✓ 模型會回 `finish_reason: tool_calls` |
| 伺服器自動執行 search | ✗ 需 **Laravel tool loop**（收 tool_calls → 執行 search → `role: tool` → 再呼叫 LLM） |
| `/tools` endpoint | ✗ 404 |

**M8-B 架構含意**：`GroundingService` + `LocalLlmWebSearchGroundingProvider` 為 **其中一種** Admin 可選 strategy；Search API strategy 同 spec 並列。

---

## 5. 文件影響矩陣

| 文件 | M8-A | M8-B | M8-C |
|------|------|------|------|
| `07-milestones.md` | §M8-A | §M8-B | §M8-C |
| `09-grounding-and-trust.md` | — | **新建** | 可能 §semantic |
| `02-contracts.md` | audit 狀態欄位 | grounding DTO | aligner 輸出 |
| `03-consensus-algorithm.md` | — | metadata 流程 | §對齊 |
| `04-trust-level.md` | — | cap 調整表 | — |
| `06-test-scenarios.md` | HTTP/UI tests | F15+ | F08 擴充 |
| `README.md` | 路由、async | grounding 說明 | — |

---

## 6. 下一步（Orchestrator）

- [x] 撰寫 `docs/09-grounding-and-trust.md`
- [x] 發布 **M8-B** Worker brief
- [x] Worker 實作 M8-B → **RELEASED**（2026-06-14）
- [x] 撰寫 `docs/10-product-ux-and-async.md` + 發布 **M8-A** brief
- [x] Worker 實作 M8-A → **RELEASED**（2026-06-14）
- [ ] 撰寫 M8-C semantic spec → brief → RELEASED
