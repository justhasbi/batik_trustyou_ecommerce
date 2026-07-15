<script setup>
import { reactive, ref } from 'vue'
import client from '@/api/client'
import { useCartStore } from '@/stores/cart'
import { formatPrice } from '@/utils/format'

const cart = useCartStore()

const form = reactive({
    recipient_name: '',
    recipient_phone: '',
    shipping_address: '',
    courier: '',
})
const errors = ref({})
const generalError = ref('')
const submitting = ref(false)
const order = ref(null)

async function submit() {
    submitting.value = true
    errors.value = {}
    generalError.value = ''
    try {
        const payload = { ...form }
        if (!payload.courier) delete payload.courier
        const { data } = await client.post('/checkout', payload)
        order.value = data.data
        await cart.fetch()
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {}
            generalError.value = e.response.data.message || ''
        } else {
            generalError.value = 'Terjadi kesalahan. Silakan coba lagi.'
        }
    } finally {
        submitting.value = false
    }
}
</script>

<template>
    <div class="container checkout-page">
        <div v-if="order" class="success card">
            <p class="eyebrow">Pesanan berhasil dibuat</p>
            <h1 class="success__title">Terima kasih, {{ order.recipient_name }}!</h1>
            <p class="muted">Nomor pesanan Anda:</p>
            <p class="mono success__order-number">{{ order.order_number }}</p>
            <div class="success__items">
                <div v-for="(item, i) in order.items" :key="i" class="success__item">
                    <span>{{ item.product_name }}<span v-if="item.size"> ({{ item.size }})</span> × {{ item.quantity }}</span>
                    <span class="price">{{ formatPrice(item.subtotal) }}</span>
                </div>
            </div>
            <hr class="hairline" style="margin: 16px 0" />
            <div class="success__row">
                <span class="muted">Subtotal</span>
                <span class="price">{{ formatPrice(order.subtotal) }}</span>
            </div>
            <div class="success__row">
                <span class="muted">Ongkos kirim</span>
                <span class="price">{{ formatPrice(order.shipping_cost) }}</span>
            </div>
            <div class="success__row success__row--total">
                <span>Total</span>
                <span class="price">{{ formatPrice(order.total) }}</span>
            </div>
            <RouterLink to="/" class="btn btn--block" style="margin-top: 24px">Kembali berbelanja</RouterLink>
        </div>

        <div v-else class="checkout-layout">
            <div class="checkout-form">
                <h1 class="checkout-page__title">Checkout</h1>
                <p v-if="generalError" class="form-error" style="margin-bottom: 16px">{{ generalError }}</p>

                <form @submit.prevent="submit">
                    <div class="field">
                        <label>Nama penerima</label>
                        <input v-model="form.recipient_name" type="text" class="input" required />
                        <p v-if="errors.recipient_name" class="form-error">{{ errors.recipient_name[0] }}</p>
                    </div>
                    <div class="field">
                        <label>Nomor telepon penerima</label>
                        <input v-model="form.recipient_phone" type="tel" class="input" required />
                        <p v-if="errors.recipient_phone" class="form-error">{{ errors.recipient_phone[0] }}</p>
                    </div>
                    <div class="field">
                        <label>Alamat pengiriman</label>
                        <textarea v-model="form.shipping_address" class="input" required></textarea>
                        <p v-if="errors.shipping_address" class="form-error">{{ errors.shipping_address[0] }}</p>
                    </div>
                    <div class="field">
                        <label>Kurir (opsional)</label>
                        <input v-model="form.courier" type="text" class="input" placeholder="Misal: JNE" />
                    </div>
                    <button type="submit" class="btn btn--block" :disabled="submitting || cart.items.length === 0">
                        {{ submitting ? 'Memproses…' : 'Buat Pesanan' }}
                    </button>
                    <p v-if="cart.items.length === 0" class="muted" style="margin-top: 12px; font-size: 13px">
                        Keranjang Anda kosong.
                    </p>
                </form>
            </div>

            <aside class="checkout-summary card">
                <h2 class="checkout-summary__title">Ringkasan Pesanan</h2>
                <div v-for="item in cart.items" :key="item.id" class="checkout-summary__row">
                    <span>{{ item.product_name }}<span v-if="item.size"> ({{ item.size }})</span> × {{ item.quantity }}</span>
                    <span class="price">{{ formatPrice(item.line_total) }}</span>
                </div>
                <hr class="hairline" style="margin: 16px 0" />
                <div class="checkout-summary__row checkout-summary__row--total">
                    <span>Subtotal</span>
                    <span class="price">{{ formatPrice(cart.total) }}</span>
                </div>
                <p class="muted" style="font-size: 12px; margin-top: 8px">Ongkos kirim ditentukan setelah pesanan diproses.</p>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.checkout-page {
    padding: 48px 24px 80px;
}

.checkout-page__title {
    font-size: 24px;
    font-weight: 600;
    margin: 0 0 24px;
}

.checkout-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 40px;
    align-items: start;
}

@media (max-width: 780px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }
}

.checkout-summary {
    position: sticky;
    top: 88px;
    padding: 24px;
}

.checkout-summary__title {
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 16px;
}

.checkout-summary__row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 13px;
    margin-bottom: 10px;
}

.checkout-summary__row--total {
    font-weight: 600;
    font-size: 15px;
}

.success {
    max-width: 480px;
    margin: 48px auto;
    padding: 32px;
}

.success__title {
    margin: 8px 0 16px;
    font-size: 22px;
    font-weight: 600;
}

.success__order-number {
    font-size: 16px;
    margin: 0 0 20px;
}

.success__items {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.success__item {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
}

.success__row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin-bottom: 8px;
}

.success__row--total {
    font-weight: 600;
    font-size: 15px;
}
</style>
