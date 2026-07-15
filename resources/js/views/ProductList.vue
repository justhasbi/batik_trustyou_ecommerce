<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import client from '@/api/client'
import ProductCard from '@/components/ProductCard.vue'

const route = useRoute()
const router = useRouter()

const products = ref([])
const meta = ref(null)
const loading = ref(true)
const search = ref(route.query.search || '')

async function load() {
    loading.value = true
    try {
        const { data } = await client.get('/products', {
            params: {
                search: route.query.search || undefined,
                category: route.query.category || undefined,
                page: route.query.page || undefined,
            },
        })
        products.value = data.data
        meta.value = data.meta
    } finally {
        loading.value = false
    }
}

function submitSearch() {
    router.push({ query: { ...route.query, search: search.value || undefined, page: undefined } })
}

function goToPage(page) {
    router.push({ query: { ...route.query, page } })
}

onMounted(load)
watch(() => route.query, load)
</script>

<template>
    <div>
        <section class="hero">
            <div class="container hero__inner">
                <p class="eyebrow">Koleksi Batik Otentik</p>
                <h1 class="hero__title">Batik yang bercerita, dibuat untuk dipakai sehari-hari.</h1>
                <form class="hero__search" @submit.prevent="submitSearch">
                    <input v-model="search" type="search" class="input" placeholder="Cari nama produk atau motif…" />
                    <button type="submit" class="btn btn--ghost">Cari</button>
                </form>
            </div>
        </section>

        <section class="container product-section">
            <div v-if="loading" class="empty">
                <div class="spinner" style="margin: 0 auto"></div>
            </div>
            <div v-else-if="products.length === 0" class="empty">
                <p>Tidak ada produk yang ditemukan.</p>
            </div>
            <template v-else>
                <div class="product-grid">
                    <ProductCard v-for="product in products" :key="product.id" :product="product" />
                </div>
                <div v-if="meta && meta.last_page > 1" class="pagination">
                    <button
                        type="button"
                        class="btn btn--ghost btn--sm"
                        :disabled="meta.current_page <= 1"
                        @click="goToPage(meta.current_page - 1)"
                    >
                        Sebelumnya
                    </button>
                    <span class="mono muted">Halaman {{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button
                        type="button"
                        class="btn btn--ghost btn--sm"
                        :disabled="meta.current_page >= meta.last_page"
                        @click="goToPage(meta.current_page + 1)"
                    >
                        Berikutnya
                    </button>
                </div>
            </template>
        </section>
    </div>
</template>

<style scoped>
.hero {
    border-bottom: 1px solid var(--line);
    padding: 64px 0;
}

.hero__inner {
    max-width: 640px;
}

.hero__title {
    margin: 12px 0 24px;
    font-size: clamp(28px, 4vw, 38px);
    font-weight: 600;
    line-height: 1.25;
}

.hero__search {
    display: flex;
    gap: 8px;
}

.hero__search .input {
    flex: 1;
}

.product-section {
    padding: 48px 24px 80px;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 32px 24px;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 48px;
}
</style>
