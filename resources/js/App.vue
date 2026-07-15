<script setup>
import { onMounted } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import ChatWidget from '@/components/ChatWidget.vue'
import ToastHost from '@/components/ToastHost.vue'

const auth = useAuthStore()
const cart = useCartStore()
const router = useRouter()

onMounted(async () => {
    await auth.fetchMe()
    cart.fetch()
})

async function handleLogout() {
    await auth.logout()
    cart.fetch()
    router.push('/')
}
</script>

<template>
    <div class="layout">
        <header class="site-header">
            <div class="container site-header__row">
                <RouterLink to="/" class="wordmark mono">BATIK TRUSTYOU</RouterLink>
                <nav class="site-nav">
                    <RouterLink to="/" class="site-nav__link">Katalog</RouterLink>
                    <RouterLink to="/cart" class="site-nav__link site-nav__cart">
                        Keranjang
                        <span v-if="cart.count > 0" class="cart-badge mono">{{ cart.count }}</span>
                    </RouterLink>
                    <button v-if="auth.isLoggedIn" class="site-nav__link site-nav__button" type="button" @click="handleLogout">
                        Keluar
                    </button>
                    <RouterLink v-else to="/login" class="site-nav__link">Masuk</RouterLink>
                </nav>
            </div>
        </header>

        <main class="site-main">
            <RouterView />
        </main>

        <footer class="site-footer">
            <div class="container site-footer__row">
                <span class="mono muted">© 2026 Batik TrustYou</span>
                <span class="muted">Batik asli, dibuat dengan sepenuh hati.</span>
            </div>
        </footer>

        <ChatWidget />
        <ToastHost />
    </div>
</template>

<style>
.layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.site-header {
    position: sticky;
    top: 0;
    z-index: 20;
    background: var(--paper);
    border-bottom: 1px solid var(--line);
}

.site-header__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
}

.wordmark {
    font-size: 14px;
    font-weight: 500;
    letter-spacing: 0.06em;
    text-decoration: none;
    color: var(--ink);
}

.site-nav {
    display: flex;
    align-items: center;
    gap: 24px;
}

.site-nav__link {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: var(--muted);
    text-decoration: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
}

.site-nav__link:hover,
.site-nav__link.router-link-active {
    color: var(--ink);
}

.cart-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 999px;
    background: var(--indigo);
    color: #fff;
    font-size: 11px;
}

.site-main {
    flex: 1;
}

.site-footer {
    border-top: 1px solid var(--line);
    padding: 24px 0;
}

.site-footer__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    flex-wrap: wrap;
    gap: 8px;
}
</style>
