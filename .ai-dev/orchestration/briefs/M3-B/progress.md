# M3-B Progress — SDK Adapters

| 欄位 | 值 |
|------|-----|
| Gate | **M3-B** |
| 狀態 | **RELEASED** |
| Brief | [brief.md](brief.md) |

---

## 1. 交付物檢核

- [x] `app/AI/Providers/*` SDK adapter 實作 `LlmProvider`
- [x] OpenAI backend adapter：`OpenAiLlmProvider`
- [x] Claude backend adapter：`AnthropicLlmProvider`
- [x] Gemini backend adapter：`GeminiLlmProvider`
- [x] DI / factory：`ConfiguredLlmProviderFactory` + `LlmProvider` 預設解析
- [x] 缺 key graceful degrade：回傳 `provider_unavailable`，不呼叫 SDK
- [x] Adapter tests：SDK fake 驗證 provider/model/timeout/prompt mapping
- [x] Opt-in live path：`M3_B_LIVE_OPENAI=1` 時可跑 OpenAI adapter 真呼叫

### 1.1 禁止項

- [x] `app/Consensus/` 內無 Laravel AI SDK 引用
- [x] 無 Extractor / Classifier / Consensus 算法
- [x] 根 `README.md`、`docs/` 未修改

---

## 2. 驗收命令

```bash
php artisan test --compact --filter=AiProviderAdapter
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

### 2.1 輸出紀錄

```text
$ php artisan test --compact --filter=AiProviderAdapter
Tests: 1 skipped, 5 passed (56 assertions)

$ php artisan test --compact
Tests: 1 skipped, 19 passed (128 assertions)

$ vendor/bin/pint --dirty --format agent
{"tool":"pint","result":"passed"}

$ php -r "... DI smoke ..."
App\AI\Providers\OpenAiLlmProvider
App\AI\Providers\OpenAiLlmProvider
```

> Skipped test: live OpenAI path is opt-in. Set `M3_B_LIVE_OPENAI=1` with `OPENAI_API_KEY` to run it.

---

## 3. 變更檔案清單

```text
新增:
  app/AI/Providers/RawAnswerAgent.php
  app/AI/Providers/LaravelAiLlmProvider.php
  app/AI/Providers/OpenAiLlmProvider.php
  app/AI/Providers/AnthropicLlmProvider.php
  app/AI/Providers/GeminiLlmProvider.php
  app/AI/Providers/ConfiguredLlmProviderFactory.php
  tests/Feature/AiProviderAdapterTest.php

修改:
  app/Providers/ConsensusServiceProvider.php
    - bind ConfiguredLlmProviderFactory
    - bind domain LlmProvider to configured default adapter
  config/consensus.php
    - provider enabled checks use filled API keys
    - optional OPENAI_MODEL / ANTHROPIC_MODEL / GEMINI_MODEL
  .ai-dev/orchestration/briefs/M3-B/progress.md
```

---

## 4. Worker 提交 / 5. Orchestrator 審核

| Worker 日期 | 2026-06-13 |
| **建議 Orchestrator 文件更新** | M3-B 完成後可將 gate-status M3-B 與 Milestone 3 標為 RELEASED；無 README 更新需求。 |
| 審核者 | Orchestrator |
| 日期 | 2026-06-13 |
| 結果 | ☑ **RELEASED** · ☐ REJECTED |
| **docs / README 整合** | ☑ 已更新（`docs/07-milestones.md` M3 完成） |
| M3 Milestone | ☑ **RELEASED** |
| Blocking | 無 |
| Non-blocking | `LlmProvider` DI 預設綁 OpenAI adapter；多 provider 編排仍用 `ConfiguredLlmProviderFactory::all()`。真並行仍留 adapter 層。 |
| 備註 | 重跑 AiProviderAdapter 5 passed + 全 suite 19 passed；`app/Consensus/` 無 SDK。M4 改 3 Gate（見 gate-status）。下一 Gate：**M4-A**。 |
