<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import client from '@/api/client'
import { formatPrice, formatDateTime, orderStatusClass } from '@/utils/format'
import { useToast } from '@/composables/toast'

const route = useRoute()
const toast = useToast()

const order = ref(null)
const loading = ref(true)
const errorMsg = ref('')
const advancing = ref(false)
const cancelling = ref(false)

const isCompleted = computed(() => order.value?.status === 'completed')
const isDelivered = computed(() => order.value?.shipping_status === 'delivered')
const canPay = computed(() =>
    order.value && order.value.status === 'pending' && order.value.payment_method !== 'cod')
const canCancel = computed(() =>
    order.value && !order.value.is_paid && order.value.status !== 'cancelled'
    && order.value.shipping_status === 'not_shipped')

async function fetchOrder() {
    loading.value = true
    try {
        const { data } = await client.get(`/orders/${route.params.id}`)
        order.value = data.data
    } catch (e) {
        errorMsg.value = 'Pesanan tidak ditemukan.'
    } finally {
        loading.value = false
    }
}

async function advanceShipping() {
    advancing.value = true
    try {
        const { data } = await client.post(`/orders/${route.params.id}/shipping/advance`)
        order.value = data.data
        if (order.value.shipping_status === 'delivered') {
            toast.push('Pesanan telah sampai. Transaksi selesai!')
        } else {
            toast.push(`Status pengiriman: ${order.value.shipping_label}`)
        }
    } catch (e) {
        toast.push(e.response?.data?.message || 'Gagal memperbarui pengiriman.')
    } finally {
        advancing.value = false
    }
}

async function cancelOrder() {
    if (!confirm('Batalkan pesanan ini?')) return
    cancelling.value = true
    try {
        const { data } = await client.post(`/orders/${route.params.id}/cancel`)
        order.value = data.data
        toast.push('Pesanan dibatalkan.')
    } catch (e) {
        toast.push(e.response?.data?.message || 'Gagal membatalkan pesanan.')
    } finally {
        cancelling.value = false
    }
}

onMounted(fetchOrder)
</script>

<template>
    <div class="container detail-page">
        <p v-if="loading" class="muted">Memuat…</p>
        <p v-else-if="errorMsg" class="form-error">{{ errorMsg }}</p>

        <div v-else-if="order">
            <RouterLink :to="{ name: 'orders' }" class="back-link muted">← Semua pesanan</RouterLink>

            <!-- Banner transaksi selesai -->
            <div v-if="isCompleted" class="done-banner">
                <span class="done-banner__icon">✓</span>
                <div>
                    <p class="done-banner__title">Transaksi Selesai</p>
                    <p class="done-banner__sub">Pesanan telah diterima pada {{ formatDateTime(order.delivered_at) }}.</p>
                </div>
            </div>

            <div class="detail-head">
                <div>
                    <p class="eyebrow">Pesanan</p>
                    <h1 class="detail-head__number mono">{{ order.order_number }}</h1>
                    <p class="muted" style="font-size: 13px">Dibuat {{ formatDateTime(order.created_at) }}</p>
                </div>
                <span class="status-badge" :class="orderStatusClass(order.status)">{{ order.status_label }}</span>
            </div>

            <div class="detail-grid">
                <!-- Kiri: pengiriman + item -->
                <div class="detail-main">
                    <!-- Timeline pengiriman -->
                    <section class="card panel">
                        <h2 class="panel__title">Status Pengiriman</h2>
                        <ol class="timeline">
                            <li
                                v-for="step in order.shipping_timeline"
                                :key="step.key"
                                class="timeline__item"
                                :class="{ 'timeline__item--done': step.done }"
                            >
                                <span class="timeline__dot"></span>
                                <span class="timeline__label">{{ step.label }}</span>
                            </li>
                        </ol>

                        <div v-if="order.tracking_number" class="tracking-box">
                            <span class="muted">No. resi ({{ order.courier }} {{ order.shipping_method }})</span>
                            <span class="mono">{{ order.tracking_number }}</span>
                        </div>

                        <!-- Tombol simulasi pelacakan -->
                        <button
                            v-if="order.is_paid && !isDelivered"
                            class="btn btn--block"
                            :disabled="advancing"
                            style="margin-top: 16px"
                            @click="advanceShipping"
                        >
                            {{ advancing ? 'Memuat…' : 'Cek Pengiriman (Simulasi)' }}
                        </button>
                        <p v-if="!order.is_paid && order.status !== 'cancelled'" class="muted" style="font-size: 12px; margin-top: 12px">
                            Pelacakan tersedia setelah pembayaran.
                        </p>
                    </section>

                    <!-- Item pesanan -->
                    <section class="card panel">
                        <h2 class="panel__title">Item Pesanan</h2>
                        <div v-for="(item, i) in order.items" :key="i" class="detail-item">
                            <span>{{ item.product_name }}<span v-if="item.size"> ({{ item.size }})</span> × {{ item.quantity }}</span>
                            <span class="price">{{ formatPrice(item.subtotal) }}</span>
                        </div>
                        <hr class="hairline" style="margin: 14px 0" />
                        <div class="detail-row"><span class="muted">Subtotal</span><span class="price">{{ formatPrice(order.subtotal) }}</span></div>
                        <div class="detail-row"><span class="muted">Ongkos kirim</span><span class="price">{{ formatPrice(order.shipping_cost) }}</span></div>
                        <div class="detail-row detail-row--total"><span>Total</span><span class="price">{{ formatPrice(order.total) }}</span></div>
                    </section>
                </div>

                <!-- Kanan: pembayaran + penerima -->
                <aside class="detail-side">
                    <section class="card panel">
                        <h2 class="panel__title">Pembayaran</h2>
                        <div class="detail-row"><span class="muted">Metode</span><span>{{ order.payment_method }}<span v-if="order.payment_channel"> · {{ order.payment_channel }}</span></span></div>
                        <div class="detail-row"><span class="muted">Kode transaksi</span><span class="mono">{{ order.transaction_code || '-' }}</span></div>
                        <div v-if="order.va_number" class="detail-row"><span class="muted">Virtual Account</span><span class="mono">{{ order.va_number }}</span></div>
                        <div class="detail-row"><span class="muted">Status</span><span :class="order.is_paid ? 'text-success' : 'text-muted'">{{ order.is_paid ? 'Lunas' : 'Belum dibayar' }}</span></div>
                        <div v-if="order.paid_at" class="detail-row"><span class="muted">Dibayar</span><span>{{ formatDateTime(order.paid_at) }}</span></div>

                        <RouterLink v-if="canPay" :to="{ name: 'payment', params: { id: order.id } }" class="btn btn--block" style="margin-top: 14px">
                            Bayar Sekarang
                        </RouterLink>
                        <button v-if="canCancel" class="btn btn--ghost btn--block" :disabled="cancelling" style="margin-top: 10px" @click="cancelOrder">
                            {{ cancelling ? 'Membatalkan…' : 'Batalkan Pesanan' }}
                        </button>
                    </section>

                    <section class="card panel">
                        <h2 class="panel__title">Penerima</h2>
                        <p class="detail-recipient">{{ order.recipient_name }}</p>
                        <p class="muted" style="font-size: 13px">{{ order.recipient_phone }}</p>
                        <p class="muted" style="font-size: 13px; margin-top: 8px">{{ order.shipping_address }}</p>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</template>

<style scoped>
.detail-page {
    padding: 40px 24px 80px;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    font-size: 13px;
    text-decoration: none;
}

.done-banner {
    display: flex;
    align-items: center;
    gap: 14px;
    background: color-mix(in srgb, #16a34a 12%, transparent);
    border: 1px solid color-mix(in srgb, #16a34a 40%, transparent);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 24px;
}

.done-banner__icon {
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    border-radius: 999px;
    background: #16a34a;
    color: #fff;
    font-size: 18px;
    flex-shrink: 0;
}

.done-banner__title {
    font-weight: 600;
    font-size: 15px;
}

.done-banner__sub {
    font-size: 13px;
    color: var(--muted);
}

.detail-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 24px;
}

.detail-head__number {
    font-size: 20px;
    font-weight: 600;
    margin: 4px 0;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
}

.is-success { background: color-mix(in srgb, #16a34a 15%, transparent); color: #15803d; }
.is-info { background: color-mix(in srgb, var(--indigo) 15%, transparent); color: var(--indigo); }
.is-pending { background: color-mix(in srgb, #d97706 15%, transparent); color: #b45309; }
.is-danger { background: color-mix(in srgb, #dc2626 15%, transparent); color: #b91c1c; }
.is-muted { background: var(--line); color: var(--muted); }
.text-success { color: #15803d; font-weight: 500; }
.text-muted { color: var(--muted); }

.detail-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 24px;
    align-items: start;
}

@media (max-width: 820px) {
    .detail-grid { grid-template-columns: 1fr; }
}

.detail-main, .detail-side {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.panel {
    padding: 22px;
}

.panel__title {
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 16px;
}

.timeline {
    list-style: none;
    padding: 0;
    margin: 0;
}

.timeline__item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 0 20px 4px;
    font-size: 13px;
    color: var(--muted);
}

.timeline__item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 9px;
    top: 16px;
    bottom: -4px;
    width: 2px;
    background: var(--line);
}

.timeline__item--done:not(:last-child)::before {
    background: var(--indigo);
}

.timeline__dot {
    width: 12px;
    height: 12px;
    border-radius: 999px;
    border: 2px solid var(--line);
    background: var(--paper);
    z-index: 1;
    flex-shrink: 0;
}

.timeline__item--done .timeline__dot {
    border-color: var(--indigo);
    background: var(--indigo);
}

.timeline__item--done .timeline__label {
    color: var(--ink);
    font-weight: 500;
}

.tracking-box {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-top: 8px;
    padding: 12px 14px;
    border: 1px dashed var(--line);
    border-radius: 8px;
    font-size: 13px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 13px;
    margin-bottom: 10px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 13px;
    margin-bottom: 8px;
}

.detail-row--total {
    font-weight: 600;
    font-size: 15px;
    margin-top: 4px;
}

.detail-recipient {
    font-weight: 500;
    font-size: 14px;
}
</style>
