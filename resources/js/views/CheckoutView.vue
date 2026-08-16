<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import client from '@/api/client'
import { useCartStore } from '@/stores/cart'
import { formatPrice } from '@/utils/format'

const cart = useCartStore()
const router = useRouter()

const form = reactive({
    recipient_name: '',
    recipient_phone: '',
    shipping_address: '',
    shipping_option_id: '',
    payment_method: '',
    payment_channel: '',
})

const shippingOptions = ref([])
const paymentMethods = ref([])
const errors = ref({})
const generalError = ref('')
const submitting = ref(false)
const loadingOptions = ref(true)

const selectedShipping = computed(() =>
    shippingOptions.value.find((o) => o.id === form.shipping_option_id) || null)
const selectedPayment = computed(() =>
    paymentMethods.value.find((m) => m.id === form.payment_method) || null)

const shippingCost = computed(() => selectedShipping.value?.cost || 0)
const grandTotal = computed(() => cart.total + shippingCost.value)

onMounted(async () => {
    try {
        const { data } = await client.get('/checkout/options')
        shippingOptions.value = data.shipping_options
        paymentMethods.value = data.payment_methods
    } catch (e) {
        generalError.value = 'Gagal memuat opsi checkout.'
    } finally {
        loadingOptions.value = false
    }
})

function onPaymentChange() {
    // reset channel ketika ganti metode
    form.payment_channel = ''
}

async function submit() {
    submitting.value = true
    errors.value = {}
    generalError.value = ''
    try {
        const payload = { ...form }
        if (!payload.payment_channel) delete payload.payment_channel
        const { data } = await client.post('/checkout', payload)
        const order = data.data
        await cart.fetch()
        // COD tidak perlu bayar online -> langsung ke detail; lainnya ke halaman pembayaran.
        if (order.payment_method === 'cod') {
            router.push({ name: 'order-detail', params: { id: order.id } })
        } else {
            router.push({ name: 'payment', params: { id: order.id } })
        }
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
        <div class="checkout-layout">
            <div class="checkout-form">
                <h1 class="checkout-page__title">Checkout</h1>
                <p v-if="generalError" class="form-error" style="margin-bottom: 16px">{{ generalError }}</p>

                <form @submit.prevent="submit">
                    <!-- Data penerima -->
                    <section class="checkout-section">
                        <h2 class="checkout-section__title">Alamat pengiriman</h2>
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
                    </section>

                    <!-- Opsi pengiriman -->
                    <section class="checkout-section">
                        <h2 class="checkout-section__title">Opsi pengiriman</h2>
                        <p v-if="loadingOptions" class="muted">Memuat opsi…</p>
                        <label
                            v-for="opt in shippingOptions"
                            :key="opt.id"
                            class="option-card"
                            :class="{ 'option-card--active': form.shipping_option_id === opt.id }"
                        >
                            <input type="radio" name="shipping" :value="opt.id" v-model="form.shipping_option_id" />
                            <span class="option-card__main">
                                <span class="option-card__name">{{ opt.courier }} — {{ opt.service }}</span>
                                <span class="option-card__meta muted">Estimasi {{ opt.etd }}</span>
                            </span>
                            <span class="option-card__price price">{{ formatPrice(opt.cost) }}</span>
                        </label>
                        <p v-if="errors.shipping_option_id" class="form-error">{{ errors.shipping_option_id[0] }}</p>
                    </section>

                    <!-- Metode pembayaran -->
                    <section class="checkout-section">
                        <h2 class="checkout-section__title">Metode pembayaran</h2>
                        <label
                            v-for="m in paymentMethods"
                            :key="m.id"
                            class="option-card"
                            :class="{ 'option-card--active': form.payment_method === m.id }"
                        >
                            <input type="radio" name="payment" :value="m.id" v-model="form.payment_method" @change="onPaymentChange" />
                            <span class="option-card__main">
                                <span class="option-card__name">{{ m.name }}</span>
                                <span class="option-card__meta muted">{{ m.desc }}</span>
                            </span>
                        </label>
                        <p v-if="errors.payment_method" class="form-error">{{ errors.payment_method[0] }}</p>

                        <!-- Pilih channel bila metode punya channel (e-wallet / bank) -->
                        <div v-if="selectedPayment && selectedPayment.channels.length" class="field" style="margin-top: 12px">
                            <label>Pilih {{ selectedPayment.id === 'bank_transfer' ? 'bank' : 'aplikasi' }}</label>
                            <select v-model="form.payment_channel" class="input" required>
                                <option value="" disabled>— pilih —</option>
                                <option v-for="c in selectedPayment.channels" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                    </section>

                    <button type="submit" class="btn btn--block" :disabled="submitting || cart.items.length === 0">
                        {{ submitting ? 'Memproses…' : 'Lanjut ke Pembayaran' }}
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
                <div class="checkout-summary__row">
                    <span class="muted">Subtotal</span>
                    <span class="price">{{ formatPrice(cart.total) }}</span>
                </div>
                <div class="checkout-summary__row">
                    <span class="muted">Ongkos kirim</span>
                    <span class="price">{{ shippingCost ? formatPrice(shippingCost) : '—' }}</span>
                </div>
                <hr class="hairline" style="margin: 16px 0" />
                <div class="checkout-summary__row checkout-summary__row--total">
                    <span>Total</span>
                    <span class="price">{{ formatPrice(grandTotal) }}</span>
                </div>
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

.checkout-section {
    margin-bottom: 32px;
}

.checkout-section__title {
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 14px;
}

.option-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border: 1px solid var(--line);
    border-radius: 10px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
}

.option-card:hover {
    border-color: var(--ink);
}

.option-card--active {
    border-color: var(--indigo);
    background: color-mix(in srgb, var(--indigo) 6%, transparent);
}

.option-card input {
    accent-color: var(--indigo);
}

.option-card__main {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
}

.option-card__name {
    font-size: 14px;
    font-weight: 500;
}

.option-card__meta {
    font-size: 12px;
}

.option-card__price {
    font-size: 14px;
    font-weight: 600;
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
</style>
