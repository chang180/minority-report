# Milestone 8 規劃草案（Orchestrator · 2026-06-14）

> **狀態**：規劃中 · **尚未**發布 Worker brief  
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
| Email verification | Fortify feature **MAY** 啟用（08 §2.4 已預留） |
| 測試 | `M8AVerificationListTest`、`M8AAsyncVerificationTest` |

**MUST NOT**：改 Trust 算法、改 aligner、引入 grounding fetch。

**Spec 需求**：Orchestrator 擴充 `docs/07-milestones.md §M8-A` + 可選 `docs/09-product-ux.md`（輕量）。

---

### M8-B — Grounding v1

**目標**：對 Type C / `requires_grounding` 問題，**可選**取得外部來源，將 `grounding_available` 設為 true，並依 [04-trust-level.md](../../docs/04-trust-level.md) 放寬 **部分** cap（**MUST NOT** 無驗證直接 High）。

| 交付物 | 說明 |
|--------|------|
| Grounding adapter | `app/Grounding/` 或 `app/AI/Grounding/` — Web Search / Fact Check 占位 |
| Question metadata | workflow 前注入 `grounding_available`、sources 摘要（**不含** API key） |
| Trust 整合 | `CascadeTrustLevelScorer` 讀取真實 grounding 狀態（**最小** diff） |
| Admin/User 設定 | **MAY** 沿用 BYOK 或 system-level search API key |
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

```text
1. Orchestrator 撰寫 docs/09-grounding-and-trust.md（M8-B spec）
2. Orchestrator 擴充 docs/07 §M8-A / §M8-B
3. 發布 M8-A brief → Worker → RELEASED
4. 發布 M8-B brief → Worker → RELEASED
5. 撰寫 semantic spec → M8-C brief
```

**理由**：M8-A 風險低、立刻改善可用性；M8-B 解決產品核心限制（Trust + Type C）；M8-C 提升 consensus 品質但 spec 面大。

---

## 4. 待決策（需 User / Orchestrator 拍板）

| # | 問題 | 選項 |
|---|------|------|
| D1 | M8-A 是否含 Email verification | A) 含 · B) 延到 M8-A-R2 |
| D2 | Grounding 資料來源 v1 | A) Tavily/Serper 等 Search API · B) 僅 Brave/Google CSE · C) 本機 stub + admin 開關 |
| D3 | Async 是否 M8-A 必做 | A) 必做（三 provider 常 >30s）· B) 僅加 timeout UX，Job 延後 |
| D4 | M8-C 是否納入 M8 或拆 M9 | A) M8-C 同 milestone · B) M9 只做 semantic + RAG |

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

- [ ] User 確認 D1–D4 或採建議預設
- [ ] 撰寫 `docs/09-grounding-and-trust.md` 初稿
- [ ] 更新 `docs/07-milestones.md` §M8
- [ ] 發布 **M8-A** Worker brief

**建議預設**：D1=B · D2=A（Tavily 或 Serper，可配置）· D3=A · D4=A
