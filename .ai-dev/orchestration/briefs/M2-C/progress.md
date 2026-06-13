# M2-C Progress — config + DI

| 欄位 | 值 |
|------|-----|
| Gate | **M2-C** |
| 狀態 | **RELEASED** |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 交付物檢核

### 1.1 `config/consensus.php`

- [x] 檔案存在且可被 `config('consensus.*')` 讀取
- [x] `number_conflict_relative_threshold` = **0.05**
- [x] `providers.openai.enabled`（env 驅動）
- [x] `providers.anthropic.enabled`
- [x] `providers.gemini.enabled`
- [x] `timeouts.provider_seconds`
- [x] `timeouts.extractor_seconds`

### 1.2 ServiceProvider

- [x] `App\Providers\ConsensusServiceProvider`（獨立 Provider，已文件化）
- [x] 已註冊於 `bootstrap/providers.php`

### 1.3 Interface → Stub 綁定

| Interface | Stub 類別 | 已 bind |
|-----------|-----------|---------|
| QuestionClassifier | `NullQuestionClassifier` | ✅ |
| LlmProvider | `NullLlmProvider` | ✅ |
| ResponseExtractor | `NullResponseExtractor` | ✅ |
| ClaimAligner | `NullClaimAligner` | ✅ |
| ConsensusAnalyzer | `NullConsensusAnalyzer` | ✅ |
| TrustLevelScorer | `NullTrustLevelScorer` | ✅ |
| VerdictReporter | `NullVerdictReporter` | ✅ |
| FakeProviderRegistry | `NullFakeProviderRegistry` | ✅ |

所有 stub 位於 `app/Consensus/Stubs/`，呼叫時 throw `RuntimeException('Not implemented until M3/M4')`，container bind 成功。

### 1.4 禁止項

- [x] **未** bind Laravel AI SDK adapter / 真 LLM backend（M3）
- [x] **無** 外部 API **呼叫**（M3）
- [x] **無** consensus 算法（M4）
- [x] **無** audit migration 變更（M2-D）

---

## 2. 驗收命令

```bash
php artisan config:clear
php artisan tinker --execute="echo config('consensus.number_conflict_relative_threshold');"
php artisan about
```

### 2.1 輸出紀錄

```text
INFO  Configuration cache cleared successfully.

0.05

Application Name .............. Minority Report
Laravel Version ............... 13.15.0
PHP Version ................... 8.4.22
```

threshold = **0.05** ✅

---

## 3. 變更檔案清單

```text
新增:
  config/consensus.php
  app/Providers/ConsensusServiceProvider.php
  app/Consensus/Stubs/Null*.php（8 個）

修改:
  bootstrap/providers.php
  .ai-dev/orchestration/briefs/M2-C/progress.md
```

---

## 4. Worker 提交

| Worker 日期 | 2026-06-13 |
| **建議 Orchestrator 文件更新** | config/consensus.php 鍵名已定案；stub 路徑 `app/Consensus/Stubs/` |

---

## 5. Orchestrator 審核

| 項目 | 內容 |
|------|------|
| 審核者 | Orchestrator |
| 日期 | 2026-06-13 |
| 結果 | ☑ **RELEASED** · ☐ REJECTED |
| **docs / README 整合** | ☑ N/A |
| Blocking | 無 |
| 備註 | 重跑：`config('consensus.number_conflict_relative_threshold')` = 0.05；8 interface DI bind OK；無 SDK adapter、無新 audit migration、`test` 2 passed。下一 Gate：**M2-D**（可與 M2-C 並行，現已可開）。 |
