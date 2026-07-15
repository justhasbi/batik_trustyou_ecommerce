import { defineStore } from 'pinia'
import client from '@/api/client'

export const useCartStore = defineStore('cart', {
    state: () => ({
        items: [],
        count: 0,
        total: 0,
        loading: false,
    }),
    actions: {
        applyCart(cart) {
            this.items = cart.items || []
            this.count = cart.count || 0
            this.total = cart.total || 0
        },
        async fetch() {
            this.loading = true
            try {
                const { data } = await client.get('/cart')
                this.applyCart(data.data)
            } finally {
                this.loading = false
            }
        },
        async add({ product_id, product_size_id, quantity }) {
            const { data } = await client.post('/cart/items', { product_id, product_size_id, quantity })
            this.applyCart(data.data)
        },
        async updateQty(itemId, quantity) {
            const { data } = await client.patch(`/cart/items/${itemId}`, { quantity })
            this.applyCart(data.data)
        },
        async remove(itemId) {
            const { data } = await client.delete(`/cart/items/${itemId}`)
            this.applyCart(data.data)
        },
    },
})
