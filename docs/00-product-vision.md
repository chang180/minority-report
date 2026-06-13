# 00 — Product Vision（產品願景）

本文件定義「關鍵報告（Minority Report）」的產品目標、哲學、MVP 邊界與成功標準摘要。技術細節見其他 spec。

---

## 1. Project Vision

「關鍵報告」是一套基於 Laravel 的 **Multi-LLM Consensus Engine**。

本專案的目標 **不是** 消除所有電子幻覺，而是：

> **降低單一模型幻覺風險，並明確揭露多模型之間的共識、分歧與不確定性。**

靈感來自電影《Minority Report》：不同預測者可能對未來產生不同預測，少數意見具有重要參考價值。本系統將此概念應用於 LLM——當多個模型對同一問題產生不同答案時，系統 **MUST NOT** 直接忽略少數意見，而 **MUST** 保留、分析、比對並產生裁決報告。

---

## 2. Core Philosophy

**Disagreement is a feature, not a bug.**

模型間的不一致本身就是資訊。差異可能代表：

- 問題本身有歧義
- 模型知識截止日不同
- 搜尋或來源不同
- 部分模型產生幻覺
- 問題涉及爭議或尚無明確答案

因此本系統不只追求「一致答案」，也 **MUST** 呈現「為什麼不一致」。

---

## 3. Known Limitations

### 3.1 Consensus is not correctness

多個模型同意，不代表答案正確。因此：

- Consensus 只代表模型群體內部一致，**不等於** 事實正確。
- Trust Level **MUST NOT** 僅依模型一致性給出高分。
- 對時效性問題，**MUST** 標記是否需要即時來源或官方文件驗證。

MVP 若尚未完成 Web Search / Fact Check，系統 **MUST NOT** 對需要即時資料的問題給出 High Trust。

### 3.2 MVP 招牌功能與 fake provider

| 類型 | MVP 現實 |
|------|----------|
| Type A | 單模型，無 consensus |
| Type B | 真實三模型幾乎永遠一致；Minority Report 難自然觸發 |
| Type C | 真正可能分裂，但被 grounding 規則封頂 Low/Unknown |

**結論**：Majority-vs-Minority 在 MVP 用真模型時幾乎沒有舞台。

**處理方式**：

1. Majority / Minority / No Consensus 路徑 **MUST** 主要由 fake provider fixtures 驗證。
2. Demo 若用真模型，**SHOULD** 選接近知識截止日或業界仍有爭議的 Type B 斷言。
3. 真實且可靠的分歧觸發，待 Phase 3 grounding 後才成熟。

---

## 4. MVP Scope

MVP **ONLY** 完成一件事：

```text
Question → Verification → Consensus → Verdict
```

的最小閉環。

MVP **不追求**：完整 Agent Framework、商業化、多租戶、完整 RAG、完整 grounding、完整 semantic alignment。

---

## 5. Non Goals

MVP 階段 **MUST NOT** 做：

- RAG / 向量資料庫
- Agent Marketplace / MCP Marketplace
- 多使用者團隊協作
- 付費機制
- 複雜工作流編排
- 多輪辯論
- 完整匿名互評
- 自動長期記憶
- Phase 3 等級的來源可信度裁定

---

## 6. Success Criteria（摘要）

MVP 成功時 **MUST** 滿足以下能力（詳見 [06-test-scenarios.md](06-test-scenarios.md)）：

1. 分類 Type A/B/C + `answer_shape` + fail-safe bias（**CT-G1–G3** + F04）
2. fake provider 跑完整 consensus workflow
3. 處理 3/3、2/3、1/3、0/3 可分析情境
4. 區分 provider raw failure 與 extractor failure
5. 辨識 Full Consensus（discrete 與 open 主鍵）
6. 辨識 Full (low-discriminability) 並限制 Trust
7. 辨識 Majority（含 direct_answer 一致但 claim 衝突）
8. `unknown` 棄權不誤產 Minority Report
9. 多軸衝突指向不同 provider 時改判 None
10. 產生 Minority Report
11. 辨識 No Consensus
12. base + caps 瀑布輸出 Trust，可由 decision table 重現
13. 保存完整 audit trail
14. Fixture 1–14 regression
15. 明確區分 MVP 字串對齊與 Phase 3 語意對齊

---

## Traceability

| 本文件章節 | description.md |
|------------|----------------|
| §1 Vision | §1 |
| §2 Philosophy | §2 |
| §3 Limitations | §3 |
| §4 MVP Scope | §4 |
| §5 Non Goals | §5 |
| §6 Success Criteria | §22 |
