<?php

namespace App\Consensus\Extractor;

/**
 * Coerces free-text provider claims (often plain strings from local LLMs)
 * into structured consensus claims, with numeric/temperature heuristics.
 */
class ClaimStatementParser
{
    /**
     * @return array{type: string, canonical_key: string, subject: string, predicate: string, value: string, unit: ?string, source: ?string}|null
     */
    public function parseNumericStatement(string $statement): ?array
    {
        $statement = $this->cleanStatement($statement);

        if ($statement === '') {
            return null;
        }

        $numeric = $this->extractNumericValue($statement);

        if ($numeric === null) {
            return null;
        }

        [$value, $unit] = $numeric;
        $subject = $this->extractSubject($statement);
        $predicate = $this->extractPredicate($statement);

        return [
            'type' => 'number',
            'canonical_key' => $this->canonicalize(trim($subject.' '.$predicate)),
            'subject' => $subject,
            'predicate' => $predicate,
            'value' => $value,
            'unit' => $unit,
            'source' => null,
        ];
    }

    /**
     * @return array{type: string, canonical_key: string, subject: string, predicate: string, value: string, unit: ?string, source: ?string}
     */
    public function statementClaim(string $statement): array
    {
        $statement = $this->cleanStatement($statement);

        return [
            'type' => 'statement',
            'canonical_key' => $this->canonicalize($statement),
            'subject' => '',
            'predicate' => '',
            'value' => $statement,
            'unit' => null,
            'source' => null,
        ];
    }

    private function cleanStatement(string $statement): string
    {
        $statement = trim($statement);
        $statement = preg_replace('/\$([^$]+)\$/u', '$1', $statement) ?? $statement;
        $statement = preg_replace('/\s+/u', ' ', $statement) ?? $statement;

        return trim($statement);
    }

    /**
     * @return array{0: string, 1: ?string}|null
     */
    private function extractNumericValue(string $statement): ?array
    {
        $unit = $this->inferUnit($statement);

        if (preg_match('/零下\s*(\d+(?:\.\d+)?)/u', $statement, $matches) === 1) {
            return ['-'.$matches[1], $unit];
        }

        if (preg_match('/(?:攝氏|摄氏|℃|°c)\s*零下\s*(\d+(?:\.\d+)?)/ui', $statement, $matches) === 1) {
            return ['-'.$matches[1], $unit ?? '°C'];
        }

        if (preg_match('/(-?\d+(?:\.\d+)?)\s*(?:°\s?[cC]|℃|攝氏|摄氏|度)/u', $statement, $matches) === 1) {
            return [$matches[1], $unit ?? '°C'];
        }

        if (preg_match('/(?:約|大约|大約|around|approximately|approx\.?)\s*(-?\d+(?:\.\d+)?)/ui', $statement, $matches) === 1) {
            return [$matches[1], $unit];
        }

        if (preg_match('/(-?\d+(?:\.\d+)?)/u', $statement, $matches) === 1) {
            return [$matches[1], $unit];
        }

        return null;
    }

    private function inferUnit(string $statement): ?string
    {
        if (preg_match('/(?:攝氏|摄氏|℃|°\s?[cC]|度(?:$|\s))/ui', $statement) === 1) {
            return '°C';
        }

        if (preg_match('/(?:華氏|摄氏|°\s?[fF])/u', $statement) === 1) {
            return '°F';
        }

        return null;
    }

    private function extractSubject(string $statement): string
    {
        if (preg_match('/(水銀|汞|mercury|\bhg\b)/ui', $statement, $matches) === 1) {
            return mb_strtolower($matches[1]) === 'hg' ? 'mercury' : trim($matches[1]);
        }

        if (preg_match('/^([\p{L}\p{N}]{1,20})/u', $statement, $matches) === 1) {
            return trim($matches[1]);
        }

        return '';
    }

    private function extractPredicate(string $statement): string
    {
        if (preg_match('/(熔點|溶點|凝固點|沸點|boiling point|melting point)/ui', $statement, $matches) === 1) {
            return $this->normalizePredicate($matches[1]);
        }

        if (preg_match('/(溫度|温度)/u', $statement) === 1) {
            return '溫度';
        }

        return '';
    }

    private function normalizePredicate(string $predicate): string
    {
        $predicate = mb_strtolower(trim($predicate));

        return match (true) {
            str_contains($predicate, 'melting') => '熔點',
            str_contains($predicate, 'boiling') => '沸點',
            in_array($predicate, ['溶點', '凝固點'], true) => '熔點',
            default => $predicate,
        };
    }

    private function canonicalize(string $value): string
    {
        $value = $this->normalizePredicateSynonyms($value);
        $value = mb_strtolower($value);
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function normalizePredicateSynonyms(string $value): string
    {
        return str_replace(['溶點', '凝固點'], '熔點', $value);
    }
}
