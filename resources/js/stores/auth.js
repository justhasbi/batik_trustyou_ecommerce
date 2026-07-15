import { defineStore } from 'pinia'
import client from '@/api/client'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('auth_token') || null,
    }),
    getters: {
        isLoggedIn: (state) => !!state.token,
    },
    actions: {
        setSession(user, token) {
            this.user = user
            this.token = token
            localStorage.setItem('auth_token', token)
        },
        async register(payload) {
            const { data } = await client.post('/register', payload)
            this.setSession(data.user, data.token)
            return data
        },
        async login(payload) {
            const { data } = await client.post('/login', payload)
            this.setSession(data.user, data.token)
            return data
        },
        async fetchMe() {
            if (!this.token) return
            try {
                const { data } = await client.get('/me')
                this.user = data.user
            } catch {
                this.user = null
                this.token = null
                localStorage.removeItem('auth_token')
            }
        },
        async logout() {
            try {
                if (this.token) await client.post('/logout')
            } finally {
                this.user = null
                this.token = null
                localStorage.removeItem('auth_token')
            }
        },
    },
})
