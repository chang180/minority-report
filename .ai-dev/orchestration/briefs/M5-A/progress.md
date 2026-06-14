# M5-A Progress — Audit Trail + Replay

| 欄位 | 值 |
|------|-----|
| Gate | **M5-A** |
| 狀態 | **RELEASED** |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 完成項目

- `ConsensusReplayService::replayRequest($requestId)` — 複製 audit rows 並重跑 aligner→verdict
- `ConsensusReplayService::replayFixture($fixtureId)` — 依 `metadata.fixture_id` 找最新 request 重播
- `ConsensusReplayService::auditTrailForRequest($requestId)` — 完整 §10 稽核 payload
- `ConsensusWorkflow::replayFromPersisted()` — 與正常 workflow 共用 completion/persistence

## 2. 驗收命令

```bash
php artisan test --compact tests/Feature/M5AReplayAuditTest.php
php artisan test --compact
```

### 2.1 輸出紀錄

```text
M5AReplayAuditTest: 3 passed (86 assertions)
Full suite: 1 skipped, 90 passed (484 assertions)
```

## 3. MUST NOT 確認

- ✅ 未修改 `docs/`、根 `README.md`
- ✅ 未實作 Minimal UI（M6）
- ✅ 未改 consensus 算法

---

## 4. Worker 提交 / 5. Orchestrator 審核

| Worker 日期 | 2026-06-13 |
| **建議 Orchestrator 文件更新** | 區分 DB `fixture_id` replay vs fake registry replay；無 migration。 |
| 審核者 | Orchestrator |
| 日期 | 2026-06-14 |
| 結果 | ☑ **RELEASED** · ☐ REJECTED |
| **docs / README 整合** | ☑ 已更新 |
| M5 Milestone | ☑ **RELEASED** |
| Blocking | 無 |
| Non-blocking | replay 為 service 層（無 HTTP API）；`fixture_id` replay 查 DB 最新 request，非 `FakeProviderRegistry`。 |
| 備註 | 下一 Gate：**M6-A**（Minimal UI）。 |
