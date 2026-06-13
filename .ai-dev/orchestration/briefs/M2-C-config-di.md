# Worker Brief — Gate M2-C

**Milestone 2 · config + DI wiring**  
**前置 Gate**：M2-B **RELEASED**  
**狀態**：BLOCKED

---

## 角色

Worker Agent。**只做 M2-C**：設定檔 + ServiceProvider 將 interface 綁到 **placeholder** 實作。

---

## 必讀

1. [handoff.md](../handoff.md) Top 10
2. [docs/02-contracts.md](../../../docs/02-contracts.md)
3. [docs/03-consensus-algorithm.md](../../../docs/03-consensus-algorithm.md) §5（number 5% 門檻）
4. [docs/04-trust-level.md](../../../docs/04-trust-level.md) §2 cap 表
5. 本 brief

---

## 交付物

### `config/consensus.php`

至少包含（可 env 覆寫）：

```php
return [
    'number_conflict_relative_threshold' => 0.05, // 5%
    'providers' => [
        'openai' => ['enabled' => env('OPENAI_API_KEY') !== null],
        'anthropic' => ['enabled' => env('ANTHROPIC_API_KEY') !== null],
        'gemini' => ['enabled' => env('GEMINI_API_KEY') !== null],
    ],
    'timeouts' => [
        'provider_seconds' => 60,
        'extractor_seconds' => 30,
    ],
];
```

### ServiceProvider

- 新增 `App\Providers\ConsensusServiceProvider`（或擴展現有 AppServiceProvider，擇一並文件化）
- 將 02 §9 各 interface 綁定到 **Null / Stub** 實作（例如 `NullQuestionClassifier` 回傳固定 DTO 或 throw `RuntimeException('Not implemented until M4')`）
- **MUST NOT** 綁定真 LLM / Laravel AI SDK（M3）

### 註冊

在 `bootstrap/providers.php`（Laravel 11+）或等價處註冊 Provider。

---

## MUST NOT

- 實作 consensus 算法（M4）
- 安裝 `laravel/ai` 或呼叫外部 API（M3）
- 修改 migration（M2-D）

---

## 驗收

```bash
php artisan config:clear
php artisan tinker --execute="echo config('consensus.number_conflict_relative_threshold');"
php artisan about
```

確認 `config/consensus.php` 可被載入且 DI 解析 interface 不拋 autoload 錯誤（stub 可 deliberate throw 若被呼叫，但 container bind 須成功）。

---

## 完成後交還

1. config 鍵清單
2. 各 interface → stub 對照表
3. 留給 M2-D / M3 事項
