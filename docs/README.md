# 關鍵報告（Minority Report）— Spec 文件索引

本目錄為 spec-driven 開發的**正式規格書**。決策來源為 [.ai-dev/description.md](../.ai-dev/description.md)；若 spec 與 description 在 Laravel 版本上不一致，以 [plan.md](../.ai-dev/plan.md) 的「技術決策更新」為準（**Laravel 13**）。

## 文件清單與依賴順序

| 文件 | 用途 | 依賴 |
|------|------|------|
| [02-contracts.md](02-contracts.md) | DTO、狀態欄位、Interface 契約 | description §6–11, §18 |
| [03-consensus-algorithm.md](03-consensus-algorithm.md) | 對齊、衝突、Cases、Minority Report | 02 |
| [05-failure-modes.md](05-failure-modes.md) | Provider / Extractor 失敗狀態機 | 02 |
| [04-trust-level.md](04-trust-level.md) | Base + caps 瀑布、decision table | 03 |
| [06-test-scenarios.md](06-test-scenarios.md) | Fixture 1–14、Success Criteria | 03, 04, 05 |
| [00-product-vision.md](00-product-vision.md) | 願景、MVP 邊界、Non Goals | — |
| [01-architecture.md](01-architecture.md) | 架構、Tech Stack、延遲策略 | 02 |
| [07-milestones.md](07-milestones.md) | Milestone 1–6 拆解 | 全部 |

**在上述文件完成前，不得直接開始實作 Laravel application code。**

---

## Spec 撰寫規範

所有 agent 撰寫或修訂 spec 時 MUST 遵守：

1. **不得**改寫或弱化 `description.md` 已拍板的決策（T1–T3、§12.5 棄權、§12.6 多軸收斂、§16.5 Insufficient/Failure 互斥等）。
2. 使用 **MUST / MUST NOT / SHOULD** 語氣（RFC 2119 風格）。
3. 每份 spec 末尾附 **Traceability**：對應 `description.md` 章節編號。
4. 術語以 [02-contracts.md](02-contracts.md) 為 canonical；其他文件 MUST 引用相同用語。
5. Tech Stack 寫入 **Laravel 13**、PHP 8.4+。

---

## 術語表（Canonical）

| 術語 | 定義 |
|------|------|
| `provider_status` | Provider raw answer 呼叫狀態：`success` \| `failed_timeout` \| `provider_unavailable` \| `provider_error` |
| `extraction_status` | Normalized DTO 抽取狀態：`not_started` \| `success` \| `invalid_json` \| `extraction_failed` |
| 可分析 success | `provider_status = success` AND `extraction_status = success` |
| `answer_shape` | `discrete`（離散答案）或 `open`（開放敘述） |
| `direct_answer` | discrete 題：`yes` \| `no` \| `unknown` \| `not_applicable`（open 題專用） |
| `unknown` | discrete 題的棄權；計票時排除，不得觸發 Minority Report |
| `canonical_key` | Extractor 產生的正規化 claim 鍵，供字串對齊 |
| `aligned claim` | 正規化 `canonical_key` 相同且出現在 ≥2 provider 的 claim |
| `unmatched claim` | 只出現在單一 provider 的 claim |
| `unalignable` | `unit` 不同且無法換算，不計衝突但 MUST surface |
| 重大 claim 衝突 | `{boolean, date, number, version}` 型 aligned claim 存在衝突 |
| low-discriminability | open 題抽不出任何 `{boolean, date, number, version}` 型 claim |
| base trust | 由 consensus 結果決定的初始信任等級 |
| cap | 限制 trust 上限的條件；`final_trust = min(base, all_applicable_caps)` |
| 可分析 provider 數 | 可分析 success 的 provider 數；== 2 時 Medium cap（Case 4 / F05） |
| 有效 direct_answer 表態數 | discrete 題排除 `unknown` 棄權後的表態數；== 2 時 Medium cap（F13）；與可分析數獨立 |
