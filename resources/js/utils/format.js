export function formatPrice(value) {
    const number = Number(value) || 0
    return 'Rp' + number.toLocaleString('id-ID')
}

export function formatDateTime(value) {
    if (!value) return '-'
    const d = new Date(value.replace(' ', 'T'))
    if (isNaN(d)) return value
    return d.toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

// Warna badge status pesanan (dipakai untuk class CSS)
export function orderStatusClass(status) {
    return {
        pending: 'is-pending',
        paid: 'is-info',
        processing: 'is-info',
        completed: 'is-success',
        cancelled: 'is-danger',
    }[status] || 'is-muted'
}
