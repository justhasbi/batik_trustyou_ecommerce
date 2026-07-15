<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import { formatPrice } from '@/utils/format'

const cart = useCartStore()
const router = useRouter()

async function changeQty(item, delta) {
    const next = item.quantity + delta
    if (next < 1) return
    await cart.updateQty(item.id, next)
}

async function removeItem(item) {
    await cart.remove(item.id)
}

function goToCheckout() {
    router.push('/checkout')
}

onMounted(() => cart.fetch())
</script>

<template>
    <div class="container cart-page">
        <h1 class="cart-page__title">Keranjang Belanja</h1>

        <div v-if="cart.loading" class="empty">
            <div class="spinner" style="margin: 0 auto"></div>
        </div>
        <div v-else-if="cart.items.length === 0" class="empty">
            <p>Keranjang Anda masih kosong.</p>
            <RouterLink to="/" class="btn btn--ghost" style="margin-top: 16px; display: inline-flex">
                Mulai belanja
            </RouterLink>
        </div>
        <div v-else class="cart-layout">
            <div class="cart-items">
                <div v-for="item in cart.items" :key="item.id" class="cart-item hairline">
                    <div class="cart-item__image">
                        <img v-if="item.image" :src="item.image" :alt="item.product_name" />
                    </div>
                    <div class="cart-item__body">
                        <p class="cart-item__name">{{ item.product_name }}</p>
                        <p v-if="item.size" class="muted mono" style="font-size: 13px">Ukuran: {{ item.size }}</p>
                        <p class="price">{{ formatPrice(item.price) }}</p>
                    </div>
                    <div class="cart-item__actions">
                        <div class="stepper">
                            <button type="button" class="stepper__btn" @click="changeQty(item, -1)">−</button>
                            <span class="mono stepper__value">{{ item.quantity }}</span>
                            <button type="button" class="stepper__btn" @click="changeQty(item, 1)">+</button>
                        </div>
                        <button type="button" class="cart-item__remove" @click="removeItem(item)">Hapus</button>
                    </div>
                    <p class="price cart-item__subtotal">{{ formatPrice(item.line_total) }}</p>
                </div>
            </div>

            <aside class="cart-summary card">
                <h2 class="cart-summary__title">Ringkasan</h2>
                <div class="cart-summary__row">
                    <span class="muted">Subtotal</span>
                    <span class="price">{{ formatPrice(cart.total) }}</span>
                </div>
                <p class="muted cart-summary__note">Ongkos kirim dihitung pada langkah checkout.</p>
                <hr class="hairline" style="margin: 16px 0" />
                <div class="cart-summary__row cart-summary__row--total">
                    <span>Total</span>
                    <span class="price">{{ formatPrice(cart.total) }}</span>
                </div>
                <button type="button" class="btn btn--block" style="margin-top: 20px" @click="goToCheckout">
                    Checkout
                </button>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.cart-page {
    padding: 48px 24px 80px;
}

.cart-page__title {
    font-size: 24px;
    font-weight: 600;
    margin: 0 0 32px;
}

.cart-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 40px;
    align-items: start;
}

@media (max-width: 780px) {
    .cart-layout {
        grid-template-columns: 1fr;
    }
}

.cart-item {
    display: grid;
    grid-template-columns: 72px 1fr auto auto;
    gap: 16px;
    align-items: center;
    padding: 20px 0;
}

.cart-item__image {
    width: 72px;
    height: 72px;
    border-radius: var(--radius);
    overflow: hidden;
    background: var(--paper-2);
    border: 1px solid var(--line);
}

.cart-item__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cart-item__name {
    margin: 0 0 4px;
    font-weight: 500;
}

.cart-item__actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}

.cart-item__remove {
    background: none;
    border: none;
    color: var(--danger);
    font-size: 12px;
    cursor: pointer;
    padding: 0;
}

.cart-item__subtotal {
    min-width: 90px;
    text-align: right;
}

.cart-summary {
    position: sticky;
    top: 88px;
    padding: 24px;
}

.cart-summary__title {
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 16px;
}

.cart-summary__row {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    margin-bottom: 8px;
}

.cart-summary__row--total {
    font-weight: 600;
    font-size: 15px;
}

.cart-summary__note {
    font-size: 12px;
    margin: 0;
}
</style>
