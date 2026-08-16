import axios from 'axios'

// crypto.randomUUID() hanya tersedia di secure context (HTTPS / localhost).
// Saat diakses lewat http://IP:port, fungsi itu tidak ada, jadi sediakan fallback.
function uuidv4() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID()
    }
    if (typeof crypto !== 'undefined' && typeof crypto.getRandomValues === 'function') {
        const b = crypto.getRandomValues(new Uint8Array(16))
        b[6] = (b[6] & 0x0f) | 0x40
        b[8] = (b[8] & 0x3f) | 0x80
        const h = [...b].map((x) => x.toString(16).padStart(2, '0'))
        return `${h[0]}${h[1]}${h[2]}${h[3]}-${h[4]}${h[5]}-${h[6]}${h[7]}-${h[8]}${h[9]}-${h[10]}${h[11]}${h[12]}${h[13]}${h[14]}${h[15]}`
    }
    // Cadangan terakhir (bukan kriptografis, cukup untuk token keranjang tamu)
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0
        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16)
    })
}

function getCartToken() {
    let t = localStorage.getItem('cart_token')
    if (!t) {
        t = uuidv4()
        localStorage.setItem('cart_token', t)
    }
    return t
}

const client = axios.create({ baseURL: '/api', headers: { Accept: 'application/json' } })

client.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token')
    if (token) config.headers.Authorization = `Bearer ${token}`
    config.headers['X-Cart-Token'] = getCartToken()
    return config
})

export default client
