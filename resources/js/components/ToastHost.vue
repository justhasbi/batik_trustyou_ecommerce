<script setup>
import { useToast } from '@/composables/toast'

const { toasts, dismiss } = useToast()

function runAction(toast) {
    toast.action.onClick()
    dismiss(toast.id)
}
</script>

<template>
    <div class="toast-host">
        <TransitionGroup name="toast" tag="div">
            <div v-for="toast in toasts" :key="toast.id" class="toast card">
                <p class="toast__message">{{ toast.message }}</p>
                <button
                    v-if="toast.action"
                    type="button"
                    class="toast__action mono"
                    @click="runAction(toast)"
                >
                    {{ toast.action.label }}
                </button>
                <button type="button" class="toast__close" aria-label="Tutup" @click="dismiss(toast.id)">×</button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-host {
    position: fixed;
    left: 24px;
    bottom: 24px;
    z-index: 60;
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: min(340px, calc(100vw - 48px));
}

.toast {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    box-shadow: 0 8px 24px rgba(23, 23, 28, 0.1);
}

.toast__message {
    flex: 1;
    margin: 0;
    font-size: 14px;
}

.toast__action {
    background: none;
    border: none;
    color: var(--indigo);
    font-size: 12px;
    letter-spacing: 0.03em;
    cursor: pointer;
    text-decoration: underline;
    padding: 0;
    white-space: nowrap;
}

.toast__close {
    background: none;
    border: none;
    color: var(--muted);
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    padding: 0;
}

.toast-enter-active,
.toast-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(8px);
}
</style>
