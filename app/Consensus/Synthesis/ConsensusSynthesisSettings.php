<?php

namespace App\Consensus\Synthesis;

final readonly class ConsensusSynthesisSettings
{
    private const SLOTS = ['openai', 'anthropic', 'gemini'];

    public function __construct(
        public bool $enabled,
        public string $synthesizerSlot,
    ) {}

    /**
     * @param  array<string, mixed>|null  $consensusSlots
     */
    public static function resolve(?array $consensusSlots): self
    {
        $slots = $consensusSlots ?? [];
        $defaultEnabled = (bool) config('consensus.synthesis.enabled_by_default', true);
        $defaultSlot = (string) config('consensus.synthesis.default_slot', 'gemini');

        $enabled = array_key_exists('synthesis_enabled', $slots)
            ? filter_var($slots['synthesis_enabled'], FILTER_VALIDATE_BOOL)
            : $defaultEnabled;

        $synthesizerSlot = is_string($slots['synthesizer_slot'] ?? null) && $slots['synthesizer_slot'] !== ''
            ? $slots['synthesizer_slot']
            : $defaultSlot;

        if (! in_array($synthesizerSlot, self::SLOTS, true)) {
            $synthesizerSlot = $defaultSlot;
        }

        return new self($enabled, $synthesizerSlot);
    }

    /**
     * @return array{synthesis_enabled: bool, synthesizer_slot: string}
     */
    public function toArray(): array
    {
        return [
            'synthesis_enabled' => $this->enabled,
            'synthesizer_slot' => $this->synthesizerSlot,
        ];
    }
}
