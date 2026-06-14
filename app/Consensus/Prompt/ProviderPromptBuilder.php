<?php

namespace App\Consensus\Prompt;

use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\Question;

class ProviderPromptBuilder
{
    public function build(Question $question, ClassificationResult $classification, ?string $groundingContext = null): string
    {
        $lines = [
            '請為共識驗證回答以下問題。',
            '問題：'.$question->text,
            '預期答案型態：'.$classification->answerShape,
            '摘要（summary）請使用繁體中文撰寫。',
        ];

        if ($classification->answerShape === 'discrete') {
            $lines[] = 'direct_answer 請設為以下其中之一（小寫英文）：yes、no、unknown。';
            $lines[] = 'JSON 範例：{"direct_answer":"yes","summary":"一句繁體中文摘要。","claims":[],"citations":[]}';
        } else {
            $lines[] = '此為開放式問題，direct_answer 請設為 not_applicable。';
        }

        if ($this->looksNumericQuestion($question->text)) {
            $lines[] = '此問題要求具體數值。claims 必須是物件陣列（不可只寫字串），至少一筆 type=number。';
            $lines[] = 'number claim 範例：{"type":"number","canonical_key":"水銀 熔點","subject":"水銀","predicate":"熔點","value":"-38.83","unit":"°C"}';
        }

        if ($groundingContext !== null && trim($groundingContext) !== '') {
            $lines[] = '';
            $lines[] = trim($groundingContext);
        }

        return implode("\n", $lines);
    }

    private function looksNumericQuestion(string $text): bool
    {
        return preg_match('/(?:幾度|多少度|多少|數值|溫度|温度|°|℃|攝氏|摄氏|melting|boiling|point)/ui', $text) === 1;
    }
}
