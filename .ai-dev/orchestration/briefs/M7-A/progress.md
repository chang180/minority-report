# M7-A Progress — Fortify + Vue Kit Auth 基礎

| 欄位 | 值 |
|------|-----|
| Gate | **M7-A** |
| 狀態 | **RELEASED**（2026-06-14 · M7-A-R1 繁中 UI） |
| Brief | [brief.md](brief.md) |
| Gate 總表 | [../../gate-status.md](../../gate-status.md) |

---

## 1. 實作摘要

M7-A-R1 已完成繁體中文 UI 修正：layout、auth、settings、Dashboard、Welcome、Verification Index/Show 的使用者可見文案改為繁中；保留 domain/API 契約值（如 `Majority`、`provider_status`、fixture id）為英文。

同時新增 Laravel 原生 `lang/zh_TW` 翻譯檔，讓 Fortify / password reset / validation 的使用者可見 flash 與錯誤訊息回傳繁中，並將 app 預設 locale / fallback locale 調整為 `zh_TW`。

**上一版（2026-06-14 · 已 RELEASED 後退回）**：Fortify、Welcome、/demo、layouts、auth/settings 英文 kit 文案。

## 2. 交付物對照

### 原 M7-A（已完成 · 勿 regress）

- [x] Fortify + Inertia auth
- [x] Vue starter kit layouts / shadcn-vue 基礎
- [x] users.role + admin middleware
- [x] Welcome `/` + Demo `/demo` 路由
- [x] M7AAuthTest + M6 測試路由更新

### M7-A-R1 繁中 UI

- [x] 全 M7-A 頁面使用者可見文案改繁體中文（08 §3.4）
- [x] Verification Index/Show 繁中
- [x] validation / flash 繁中
- [x] 測試更新；suite 綠

## 3. 驗收

```text
npm run typecheck
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

## 4. Worker 提交

| Worker 日期 | 2026-06-14 |
| **建議 Orchestrator 文件更新** | `.env.example` 建議同步 `APP_LOCALE=zh_TW`、`APP_FALLBACK_LOCALE=zh_TW`；README 若描述 UI 語言，建議明確標註產品唯一顯示語言為繁體中文。 |

## 5. Orchestrator 審核

### 2026-06-14 · 初次 RELEASED

| 結果 | ☑ RELEASED |
| 備註 | 功能 OK；UI 英文過多 |

### 2026-06-14 · 退回 M7-A-R1

| 審核者 | Orchestrator |
| 結果 | ☑ **重新 RELEASED** |
| 驗收 | typecheck ✓ · pint ✓ · 102 tests passed |
| 備註 | 繁中 UI + `lang/zh_TW` + locale `zh_TW`；`.env.example` / README 已整合 |
