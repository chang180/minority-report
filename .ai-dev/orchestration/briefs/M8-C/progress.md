# M8-C Progress — Semantic Alignment

| 欄位 | 值 |
|------|-----|
| Gate | **M8-C** |
| 狀態 | **RELEASED** |
| Brief | [brief.md](brief.md) |
| Spec | [docs/11-semantic-alignment.md](../../../../docs/11-semantic-alignment.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 實作摘要

| 項目 | 狀態 | 說明 |
|------|------|------|
| `system_aligner_settings` migration | ✅ | `2026_06_14_105700_create_system_aligner_settings_table.php` |
| `SystemAlignerSettings` Model | ✅ | singleton `instance()`；`local_api_key` encrypted cast |
| `SystemAlignerSettingsSeeder` | ✅ | 預設 `mode=string`，`enabled=true` |
| `app/Alignment/Contracts/SemanticEquivalenceProvider` | ✅ | `clusterKeys()` contract |
| `app/Alignment/Providers/NullSemanticEquivalenceProvider` | ✅ | string mode stub |
| `app/Alignment/Providers/LocalLlmSemanticEquivalenceProvider` | ✅ | OpenAI-compatible POST `/v1/chat/completions`，timeout，不 log key |
| `app/Alignment/ClaimAlignmentService` implements `ClaimAligner` | ✅ | 兩階段對齊；失敗 fallback + `metadata.fallback_reason` |
| `ConsensusServiceProvider` DI 改綁 | ✅ | `ClaimAligner::class` → `ClaimAlignmentService::class` |
| `AdminAlignerController` GET/PUT `/admin/aligner` | ✅ | admin only；key 以 `has_local_api_key` 曝露 |
| `Pages/Admin/AlignerSettings.vue` | ✅ | 繁中 UI；支援 string / semantic_llm 切換 |
| F16 fixture catalog entry | ✅ | `M8-F16` 加入 `ConsensusDemoFixtureCatalog` |
| `M8CSemanticAlignmentTest.php` | ✅ | 13 tests，50 assertions，全綠 |
| F01–F15 string mode 回歸 | ✅ | 180 passed，1 skipped（pre-existing） |

---

## 2. 交付物對照

- [x] Migration + Model：`system_aligner_settings`（singleton）
- [x] `encrypted` cast：`local_api_key`
- [x] Seeder：預設 `mode=string`（CI 不依賴 LLM）
- [x] `Contracts/SemanticEquivalenceProvider`
- [x] `Providers/NullSemanticEquivalenceProvider`
- [x] `Providers/LocalLlmSemanticEquivalenceProvider`（JSON structured output）
- [x] `ClaimAlignmentService` implements `ClaimAligner`
- [x] 內部使用既有 `StringClaimAligner` 作第一階段
- [x] 失敗 fallback → 字串結果 + `metadata.fallback_reason`
- [x] `ConsensusServiceProvider`：`ClaimAligner::class` → `ClaimAlignmentService::class`
- [x] `AdminAlignerController` — `GET/PUT /admin/aligner`
- [x] `Pages/Admin/Aligner.vue`（繁中）
- [x] F16 fixture catalog entry
- [x] 測試可 mock `SemanticEquivalenceProvider`（`invokeAlignWithMockedProvider` helper）
- [x] `M8CSemanticAlignmentTest.php`
- [x] F01–F15 **string mode** 回歸（現有 tests 全綠）

---

## 3. 驗收

```text
npm run typecheck        ✅ no errors
vendor/bin/pint --dirty  ✅ formatted
php artisan test --compact                 ✅ 180 passed, 1 skipped
php artisan test --filter=M8C             ✅ 13 passed (50 assertions)
```

---

## 4. Worker 提交

| Worker 日期 | 2026-06-14 |
|---|---|
| **建議 Orchestrator 文件更新** | `docs/06-test-scenarios.md` 補 F16 條目（`M8-F16`，Type B，open，三方 date claim 同值異 key）；`docs/07-milestones.md §M8-C` 標記 RELEASED；`gate-status.md` M8-C → RELEASED |

---

## 5. Orchestrator 審核

| 審核者 | Orchestrator |
| 結果 | ☑ **RELEASED** · ☐ REOPEN |
| 備註 | 180 passed / 1 skipped；typecheck 通過。`ClaimAlignmentService` 兩階段 + fallback 符合 spec。Show aligner badge 未做（spec MAY）。已補 `SystemAlignerSettingsSeeder` 至 `DatabaseSeeder`。 |
