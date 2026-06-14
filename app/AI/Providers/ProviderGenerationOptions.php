<?php

namespace App\AI\Providers;

use InvalidArgumentException;

class ProviderGenerationOptions
{
    /**
     * @return array<string, mixed>
     */
    public static function parseJson(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }

        try {
            $decoded = json_decode(trim($json), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException('額外參數必須是有效的 JSON 物件。', 0, $exception);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new InvalidArgumentException('額外參數必須是 JSON 物件（例如 {"max_tokens": 2048}）。');
        }

        return self::sanitize($decoded);
    }

    /**
     * @param  array<string, mixed>|null  $options
     */
    public static function toJson(?array $options): string
    {
        if ($options === null || $options === []) {
            return '';
        }

        return json_encode($options, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>|null  $options
     * @return array<string, mixed>
     */
    public static function sanitize(?array $options): array
    {
        if ($options === null || $options === []) {
            return [];
        }

        $sanitized = [];

        if (isset($options['max_tokens']) && is_numeric($options['max_tokens'])) {
            $sanitized['max_tokens'] = max(1, min(32768, (int) $options['max_tokens']));
        }

        if (isset($options['temperature']) && is_numeric($options['temperature'])) {
            $sanitized['temperature'] = max(0, min(2, (float) $options['temperature']));
        }

        if (isset($options['top_p']) && is_numeric($options['top_p'])) {
            $sanitized['top_p'] = max(0, min(1, (float) $options['top_p']));
        }

        foreach (['top_k', 'frequency_penalty', 'presence_penalty'] as $key) {
            if (isset($options[$key]) && is_numeric($options[$key])) {
                $sanitized[$key] = (float) $options[$key];
            }
        }

        foreach ($options as $key => $value) {
            if (! is_string($key) || ! preg_match('/^[a-z][a-z0-9_]*$/i', $key)) {
                continue;
            }

            if (array_key_exists($key, $sanitized)) {
                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value)) {
                $sanitized[$key] = $value;

                continue;
            }

            if (is_string($value) && mb_strlen($value) <= 256) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    public static function fromRequest(?string $json): ?array
    {
        $options = self::parseJson($json);

        return $options === [] ? null : $options;
    }
}
