<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import QRCode from 'qrcode'
import client from '@/api/client'
import { formatPrice, formatDateTime } from '@/utils/format'
import { useToast } from '@/composables/toast'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const order = ref(null)
const loading = ref(true)
const paying = ref(false)
const errorMsg = ref('')
const qrDataUrl = ref('')

const isQr = computed(() => ['qris', 'ewallet'].includes(order.value?.payment_method))
const isVa = computed(() => order.value?.payment_method === 'bank_transfer')

async function fetchOrder() {
    loading.value = true
    try {
        const { data } = await client.get(`/orders/${route.params.id}`)
        order.value = data.data
        // Jika sudah dibayar, langsung arahkan ke detail.
        if (order.value.is_paid) {
            router.replace({ name: 'order-detail', params: { id: order.value.id } })
        }
    } catch (e) {
        errorMsg.value = 'Pesanan tidak ditemukan.'
    } finally {
        loading.value = false
    }
}

// Render QR dari qr_payload ke data URL saat order siap.
watch(order, async (o) => {
    if (o?.qr_payload && isQr.value) {
        try {
            qrDataUrl.value = await QRCode.toDataURL(o.qr_payload, { width: 240, margin: 1 })
        } catch {
            qrDataUrl.value = ''
        }
    }
})

async function copy(text) {
    try {
        await navigator.clipboard.writeText(text)
        toast.push('Disalin ke clipboard')
    } catch {
        /* abaikan */
    }
}

async function pay() {
    paying.value = true
    errorMsg.value = ''
    try {
        await client.post(`/orders/${route.params.id}/pay`)
        toast.push('Pembayaran berhasil (dummy)')
        router.push({ name: 'order-detail', params: { id: route.params.id } })
    } catch (e) {
        errorMsg.value = e.response?.data?.message || 'Pembayaran gagal. Coba lagi.'
    } finally {
        paying.value = false
    }
}

onMounted(fetchOrder)
</script>

<template>
    <div class="container pay-page">
        <p v-if="loading" class="muted">Memuat…</p>
        <p v-else-if="errorMsg && !order" class="form-error">{{ errorMsg }}</p>

        <div v-else-if="order" class="pay-card card">
            <p class="eyebrow">Selesaikan Pembayaran</p>
            <h1 class="pay-card__title">{{ order.order_number }}</h1>

            <div class="pay-amount">
                <span class="muted">Total tagihan</span>
                <span class="pay-amount__value price">{{ formatPrice(order.total) }}</span>
            </div>

            <div v-if="order.payment_expires_at" class="pay-expire muted">
                Bayar sebelum {{ formatDateTime(order.payment_expires_at) }}
            </div>

            <!-- Kode transaksi -->
            <div class="pay-code">
                <span class="muted">Kode transaksi</span>
                <button type="button" class="pay-code__value mono" @click="copy(order.transaction_code)">
                    {{ order.transaction_code }} <span class="pay-code__copy">salin</span>
                </button>
            </div>

            <!-- QR (QRIS / e-wallet) -->
            <div v-if="isQr" class="pay-qr">
                <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR pembayaran" class="pay-qr__img" />
                <div v-else class="pay-qr__placeholder">Menyiapkan QR…</div>
                <p class="muted pay-qr__hint">
                    Scan QR di atas dengan aplikasi
                    {{ order.payment_channel || 'bank / e-wallet' }} Anda.
                </p>
            </div>

            <!-- Virtual Account (transfer bank) -->
            <div v-else-if="isVa" class="pay-va">
                <span class="muted">Nomor Virtual Account {{ order.payment_channel }}</span>
                <button type="button" class="pay-va__number mono" @click="copy(order.va_number)">
                    {{ order.va_number }} <span class="pay-code__copy">salin</span>
                </button>
                <p class="muted pay-qr__hint">
                    Transfer tepat sebesar total tagihan ke nomor VA di atas.
                </p>
            </div>

            <p v-if="errorMsg" class="form-error" style="margin-top: 12px">{{ errorMsg }}</p>

            <p class="pay-dummy-note muted">⚠️ Ini pembayaran <strong>dummy</strong> untuk demo. Klik tombol di bawah untuk mensimulasikan pembayaran berhasil.</p>

            <button class="btn btn--block" :disabled="paying" @click="pay">
                {{ paying ? 'Memproses…' : 'Saya Sudah Bayar (Simulasi)' }}
            </button>
            <RouterLink :to="{ name: 'order-detail', params: { id: order.id } }" class="pay-later muted">
                Bayar nanti
            </RouterLink>
        </div>
    </div>
</template>

<style scoped>
.pay-page {
    padding: 48px 24px 80px;
}

.pay-card {
    max-width: 440px;
    margin: 0 auto;
    padding: 32px;
    text-align: center;
}

.pay-card__title {
    font-size: 20px;
    font-weight: 600;
    margin: 6px 0 24px;
}

.pay-amount {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 8px;
}

.pay-amount__value {
    font-size: 28px;
    font-weight: 700;
}

.pay-expire {
    font-size: 13px;
    margin-bottom: 20px;
}

.pay-code {
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: center;
    margin-bottom: 20px;
    font-size: 13px;
}

.pay-code__value,
.pay-va__number {
    border: 1px dashed var(--line);
    background: none;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 15px;
    cursor: pointer;
    color: var(--ink);
}

.pay-code__copy {
    font-size: 11px;
    color: var(--indigo);
    margin-left: 6px;
}

.pay-qr {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.pay-qr__img {
    width: 240px;
    height: 240px;
    border-radius: 12px;
    border: 1px solid var(--line);
    padding: 8px;
    background: #fff;
}

.pay-qr__placeholder {
    width: 240px;
    height: 240px;
    display: grid;
    place-items: center;
    border: 1px dashed var(--line);
    border-radius: 12px;
    color: var(--muted);
    font-size: 13px;
}

.pay-qr__hint {
    font-size: 12px;
    max-width: 280px;
}

.pay-va {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    font-size: 13px;
}

.pay-dummy-note {
    font-size: 12px;
    background: color-mix(in srgb, var(--indigo) 6%, transparent);
    border-radius: 8px;
    padding: 10px 12px;
    margin: 8px 0 20px;
    line-height: 1.5;
}

.pay-later {
    display: inline-block;
    margin-top: 16px;
    font-size: 13px;
    text-decoration: underline;
}
</style>
