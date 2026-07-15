import { reactive } from 'vue'

const toasts = reactive([])
let nextId = 1

function dismiss(id) {
    const index = toasts.findIndex((t) => t.id === id)
    if (index !== -1) toasts.splice(index, 1)
}

function push(message, { action = null, duration = 4000 } = {}) {
    const id = nextId++
    toasts.push({ id, message, action })
    if (duration) {
        setTimeout(() => dismiss(id), duration)
    }
    return id
}

export function useToast() {
    return { toasts, push, dismiss }
}
