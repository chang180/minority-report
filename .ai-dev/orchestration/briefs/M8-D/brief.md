# M8-D — 產品文案繁中 + partial verdict

**狀態**：RELEASED（2026-06-14）

## 目標

1. Provider prompt 強制 `summary` 等自然語言欄位使用繁體中文。
2. `final_verdict` 章節標題與固定說明改繁中。
3. 部分 provider 技術缺席時，在 `final_verdict` 註明參與席與缺席原因（不當 Minority）。
4. 訪客 Demo fixture 文案（label、description、sample_question、summary）繁中化。

## MUST NOT

- 改寫 Cases 1–6 或 Trust 算法。
- 將 `direct_answer` enum 中文化。

## 交付

| 項目 | 路徑 |
|------|------|
| Agent instructions | `app/AI/Providers/ConfiguredRawAnswerAgent.php` |
| User prompt | `app/Consensus/Prompt/ProviderPromptBuilder.php` |
| Verdict reporter | `app/Consensus/Verdict/StructuredVerdictReporter.php` |
| Show slotState | `resources/js/Pages/Verification/Show.vue` |
| Demo catalog | `app/Consensus/Demo/ConsensusDemoFixtureCatalog.php` |
| Demo UI | `resources/js/Pages/Demo/Index.vue` |
| 測試 | `StructuredVerdictReporterTest`、`ConsensusDemoFixtureCatalogTest`、M4C F05+、M6、M5A |
| 文件 | `docs/02`、`03` §10、`05` §10、`06` F05/F06、`08` §3.4/§4.3、`10` §6.3、`07` M8-D、README、handoff |

## 驗收

- `php artisan test` 全綠
- F05：`final_verdict` 含「缺席 provider」「呼叫逾時」
