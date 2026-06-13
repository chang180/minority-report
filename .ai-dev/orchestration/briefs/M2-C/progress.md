# M2-C Progress — config + DI

| 欄位 | 值 |
|------|-----|
| Gate | **M2-C** |
| 狀態 | **BLOCKED**（待 M2-B RELEASED） |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 交付物檢核

### 1.1 `config/consensus.php`

- [ ] 檔案存在且可被 `config('consensus.*')` 讀取
- [ ] `number_conflict_relative_threshold` = **0.05**
- [ ] `providers.openai.enabled`（env 驅動）
- [ ] `providers.anthropic.enabled`
- [ ] `providers.gemini.enabled`
- [ ] `timeouts.provider_seconds`
- [ ] `timeouts.extractor_seconds`

### 1.2 ServiceProvider

- [ ] `App\Providers\ConsensusServiceProvider`（或等價，已文件化）
- [ ] 已註冊於 `bootstrap/providers.php`

### 1.3 Interface → Stub 綁定

| Interface | Stub 類別 | 已 bind |
|-----------|-----------|---------|
| QuestionClassifier | | ☐ |
| LlmProvider | | ☐ |
| ResponseExtractor | | ☐ |
| ClaimAligner | | ☐ |
| ConsensusAnalyzer | | ☐ |
| TrustLevelScorer | | ☐ |
| VerdictReporter | | ☐ |
| FakeProviderRegistry | | ☐ |

### 1.4 禁止項

- [ ] **未** bind Laravel AI SDK adapter / 真 LLM backend（M3）
- [ ] **無** 外部 API **呼叫**（M3）
- [ ] **無** consensus 算法（M4）
- [ ] **無** audit migration 變更（M2-D）

---

## 2. 驗收命令

```bash
php artisan config:clear
php artisan tinker --execute="echo config('consensus.number_conflict_relative_threshold');"
php artisan about
```

### 2.1 輸出紀錄

```text
（Worker 貼上；threshold 應為 0.05）
```

---

## 3. Worker 提交 / 4. Orchestrator 審核

| Worker 日期 | |
| **建議 Orchestrator 文件更新** | |
| Orchestrator 結果 | ☐ RELEASED · ☐ REJECTED |
| **docs / README 整合** | ☐ 已更新 · ☐ N/A |
