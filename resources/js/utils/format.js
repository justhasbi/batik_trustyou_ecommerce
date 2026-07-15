export function formatPrice(value) {
    const number = Number(value) || 0
    return 'Rp' + number.toLocaleString('id-ID')
}
