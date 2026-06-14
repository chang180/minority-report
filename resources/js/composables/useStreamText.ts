import { onUnmounted, ref, watch, type Ref } from 'vue';

/**
 * Reveal text incrementally when source grows (e.g. polling partial provider answers).
 */
export function useStreamText(source: Ref<string>, charsPerTick = 12, tickMs = 35): Ref<string> {
    const displayed = ref('');
    let timer: ReturnType<typeof setInterval> | null = null;
    let target = '';

    function stop(): void {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    function tick(): void {
        if (displayed.value.length >= target.length) {
            displayed.value = target;
            stop();

            return;
        }

        displayed.value = target.slice(0, displayed.value.length + charsPerTick);
    }

    function syncTo(nextTarget: string): void {
        target = nextTarget;

        if (target === '') {
            stop();
            displayed.value = '';

            return;
        }

        if (!target.startsWith(displayed.value)) {
            displayed.value = '';
        }

        if (displayed.value.length >= target.length) {
            displayed.value = target;
            stop();

            return;
        }

        if (!timer) {
            timer = setInterval(tick, tickMs);
        }
    }

    watch(source, (value) => syncTo(value ?? ''), { immediate: true });

    onUnmounted(stop);

    return displayed;
}
