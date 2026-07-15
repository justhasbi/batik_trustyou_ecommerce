import axios from 'axios'

function getCartToken() {
    let t = localStorage.getItem('cart_token')
    if (!t) {
        t = crypto.randomUUID()
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
