<script setup>
import { ref, onMounted } from 'vue'
import client from '@/api/client'
import { formatPrice, formatDateTime, orderStatusClass } from '@/utils/format'

const orders = ref([])
const loading = ref(true)

onMounted(async () => {
    try {
        const { data } = await client.get('/orders')
        orders.value = data.data
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div class="container orders-page">
        <h1 class="orders-page__title">Pesanan Saya</h1>

        <p v-if="loading" class="muted">Memuat…</p>
        <div v-else-if="orders.length === 0" class="empty card">
            <p class="muted">Belum ada pesanan.</p>
            <RouterLink to="/" class="btn" style="margin-top: 12px">Mulai belanja</RouterLink>
        </div>

        <div v-else class="orders-list">
            <RouterLink
                v-for="o in orders"
                :key="o.id"
                :to="{ name: 'order-detail', params: { id: o.id } }"
                class="order-row card"
            >
                <div class="order-row__left">
                    <p class="order-row__number mono">{{ o.order_number }}</p>
                    <p class="muted order-row__meta">{{ formatDateTime(o.created_at) }} · {{ o.shipping_label }}</p>
                </div>
                <div class="order-row__right">
                    <span class="status-badge" :class="orderStatusClass(o.status)">{{ o.status_label }}</span>
                    <span class="price order-row__total">{{ formatPrice(o.total) }}</span>
                </div>
            </RouterLink>
        </div>
    </div>
</template>

<style scoped>
.orders-page {
    padding: 48px 24px 80px;
    max-width: 720px;
}

.orders-page__title {
    font-size: 24px;
    font-weight: 600;
    margin: 0 0 24px;
}

.empty {
    padding: 40px;
    text-align: center;
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.order-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 18px 20px;
    text-decoration: none;
    color: var(--ink);
    transition: border-color 0.15s;
}

.order-row:hover {
    border-color: var(--ink);
}

.order-row__number {
    font-size: 14px;
    font-weight: 600;
}

.order-row__meta {
    font-size: 12px;
    margin-top: 4px;
}

.order-row__right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}

.order-row__total {
    font-size: 14px;
    font-weight: 600;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 500;
    white-space: nowrap;
}

.is-success { background: color-mix(in srgb, #16a34a 15%, transparent); color: #15803d; }
.is-info { background: color-mix(in srgb, var(--indigo) 15%, transparent); color: var(--indigo); }
.is-pending { background: color-mix(in srgb, #d97706 15%, transparent); color: #b45309; }
.is-danger { background: color-mix(in srgb, #dc2626 15%, transparent); color: #b91c1c; }
.is-muted { background: var(--line); color: var(--muted); }
</style>
