export const CONSENSUS_SLOT_KEYS = ['openai', 'anthropic', 'gemini'] as const;

export type ConsensusSlotKey = (typeof CONSENSUS_SLOT_KEYS)[number];

export const CONSENSUS_SLOT_LABELS: Record<ConsensusSlotKey, string> = {
    openai: '共識席 A',
    anthropic: '共識席 B',
    gemini: '共識席 C',
};

export function consensusSlotLabel(key: string): string {
    if (key in CONSENSUS_SLOT_LABELS) {
        return CONSENSUS_SLOT_LABELS[key as ConsensusSlotKey];
    }

    return key;
}
