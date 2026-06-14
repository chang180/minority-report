# M7-A Progress — Fortify + Vue Kit Auth 基礎

| 欄位 | 值 |
|------|-----|
| Gate | **M7-A** |
| 狀態 | **RELEASED** |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 實作摘要

- Fortify + Inertia auth（register/login/logout/reset/profile/password）；未啟用 2FA
- `users.role` + admin middleware + `AdminUserSeeder`
- Welcome `/`；M6 demo 遷至 `/demo/*`（仍用 `VerificationController`）
- vue-starter-kit 選擇性移植：layouts、auth/settings、shadcn-vue 基礎、`@/` alias
- 刪除孤立 `Welcome.vue`

## 2. 交付物對照

- [x] Fortify + Inertia auth
- [x] Vue starter kit layouts / shadcn-vue 基礎
- [x] users.role + admin middleware
- [x] Welcome `/` + Demo `/demo` 路由
- [x] M7AAuthTest + M6 測試路由更新

## 3. 驗收

```text
Orchestrator 複驗（2026-06-14）:
- npm run typecheck: passed
- npm run build: passed
- php artisan test: 101 passed, 1 skipped (600 assertions)
- composer audit: no advisories
- npm audit: 0 vulnerabilities（concurrently 10 + esbuild override）
```

## 4. Worker 提交

| Worker 日期 | 2026-06-14 |
| **建議 Orchestrator 文件更新** | README 路由、`.env.example` ADMIN_*、gate-status |
| Blocking | 無 |

## 5. Orchestrator 審核

| 審核者 | Orchestrator |
| 日期 | 2026-06-14 |
| 結果 | ☑ **RELEASED** · ☐ REJECTED |
| **docs / README 整合** | ☑ 已更新 |
| npm audit | ☑ concurrently ^10、esbuild override → 0 vulnerabilities |
| Non-blocking | Demo 仍用 `VerificationController`（非獨立 `DemoVerificationController`）；可接受 |
| 備註 | Dashboard 為 placeholder；M7-B 接 provider 與產品 Dashboard |
