<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import client from '@/api/client'
import { useCartStore } from '@/stores/cart'
import { useToast } from '@/composables/toast'
import { formatPrice } from '@/utils/format'

const route = useRoute()
const router = useRouter()
const cart = useCartStore()
const toast = useToast()

const product = ref(null)
const loading = ref(true)
const notFound = ref(false)
const activeImage = ref(null)
const selectedSizeId = ref(null)
const quantity = ref(1)
const adding = ref(false)

const selectedSize = computed(() =>
    product.value?.sizes?.find((s) => s.id === selectedSizeId.value) || null
)

const canAdd = computed(() => {
    if (!product.value) return false
    if (product.value.sizes && product.value.sizes.length > 0) {
        return !!selectedSize.value && selectedSize.value.stock > 0
    }
    return true
})

async function load() {
    loading.value = true
    notFound.value = false
    product.value = null
    try {
        const { data } = await client.get(`/products/${route.params.slug}`)
        product.value = data.data
        const images = data.data.images || []
        activeImage.value = (images.find((i) => i.is_primary) || images[0])?.path || null
        selectedSizeId.value = null
        quantity.value = 1
    } catch (e) {
        notFound.value = true
    } finally {
        loading.value = false
    }
}

function selectSize(size) {
    if (size.stock <= 0) return
    selectedSizeId.value = size.id
    quantity.value = 1
}

function incrementQty() {
    const max = selectedSize.value ? selectedSize.value.stock : 99
    if (quantity.value < max) quantity.value++
}

function decrementQty() {
    if (quantity.value > 1) quantity.value--
}

async function addToCart() {
    if (!canAdd.value) return
    adding.value = true
    try {
        await cart.add({
            product_id: product.value.id,
            product_size_id: selectedSizeId.value || undefined,
            quantity: quantity.value,
        })
        toast.push('Ditambahkan ke keranjang', {
            action: { label: 'Lihat keranjang', onClick: () => router.push('/cart') },
        })
    } catch (e) {
        toast.push(e.response?.data?.message || 'Gagal menambahkan ke keranjang.')
    } finally {
        adding.value = false
    }
}

onMounted(load)
watch(() => route.params.slug, load)
</script>

<template>
    <div class="container detail">
        <div v-if="loading" class="empty">
            <div class="spinner" style="margin: 0 auto"></div>
        </div>
        <div v-else-if="notFound" class="empty">
            <p>Produk tidak ditemukan.</p>
        </div>
        <div v-else-if="product" class="detail__grid">
            <div class="gallery">
                <div class="gallery__main">
                    <img v-if="activeImage" :src="activeImage" :alt="product.name" />
                    <div v-else class="product-card__placeholder muted mono">Tidak ada gambar</div>
                </div>
                <div v-if="product.images?.length > 1" class="gallery__thumbs">
                    <button
                        v-for="image in product.images"
                        :key="image.id"
                        type="button"
                        class="gallery__thumb"
                        :class="{ 'gallery__thumb--active': image.path === activeImage }"
                        @click="activeImage = image.path"
                    >
                        <img :src="image.path" :alt="product.name" />
                    </button>
                </div>
            </div>

            <div class="detail__info">
                <div class="detail__tags">
                    <span class="tag">{{ product.fabric_type }}</span>
                    <span class="tag">{{ product.motif }}</span>
                </div>
                <h1 class="detail__name">{{ product.name }}</h1>
                <p class="price detail__price">{{ formatPrice(product.price) }}</p>
                <p class="detail__description muted">{{ product.description }}</p>

                <div v-if="product.sizes?.length" class="field">
                    <label>Ukuran</label>
                    <div class="size-pills">
                        <button
                            v-for="size in product.sizes"
                            :key="size.id"
                            type="button"
                            class="size-pill"
                            :class="{
                                'size-pill--active': size.id === selectedSizeId,
                                'size-pill--disabled': size.stock <= 0,
                            }"
                            :disabled="size.stock <= 0"
                            @click="selectSize(size)"
                        >
                            {{ size.size }}
                        </button>
                    </div>
                    <p v-if="selectedSize" class="muted" style="font-size: 13px">
                        Stok tersedia: {{ selectedSize.stock }}
                    </p>
                </div>

                <div class="field">
                    <label>Jumlah</label>
                    <div class="stepper">
                        <button type="button" class="stepper__btn" @click="decrementQty">−</button>
                        <span class="mono stepper__value">{{ quantity }}</span>
                        <button type="button" class="stepper__btn" @click="incrementQty">+</button>
                    </div>
                </div>

                <button type="button" class="btn btn--block" :disabled="!canAdd || adding" @click="addToCart">
                    {{ adding ? 'Menambahkan…' : 'Tambah ke Keranjang' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.detail {
    padding: 48px 24px 80px;
}

.detail__grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 48px;
}

@media (max-width: 720px) {
    .detail__grid {
        grid-template-columns: 1fr;
    }
}

.gallery__main {
    aspect-ratio: 3 / 4;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--paper-2);
}

.gallery__main img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-card__placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.gallery__thumbs {
    display: flex;
    gap: 10px;
    margin-top: 12px;
}

.gallery__thumb {
    width: 64px;
    height: 64px;
    padding: 0;
    border: 1px solid var(--line-2);
    border-radius: var(--radius);
    overflow: hidden;
    background: none;
    cursor: pointer;
}

.gallery__thumb--active {
    border-color: var(--indigo);
}

.gallery__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.detail__tags {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
}

.detail__name {
    margin: 0 0 8px;
    font-size: 26px;
    font-weight: 600;
    line-height: 1.3;
}

.detail__price {
    font-size: 18px;
    margin: 0 0 20px;
}

.detail__description {
    margin: 0 0 28px;
    line-height: 1.7;
    white-space: pre-line;
}

.size-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.size-pill {
    min-width: 44px;
    padding: 8px 14px;
    border-radius: 999px;
    border: 1px solid var(--line-2);
    background: var(--paper-2);
    color: var(--ink);
    font-size: 13px;
    cursor: pointer;
}

.size-pill--active {
    border-color: var(--indigo);
    background: var(--indigo-tint);
    color: var(--indigo);
}

.size-pill--disabled {
    opacity: 0.4;
    text-decoration: line-through;
    cursor: not-allowed;
}

.stepper {
    display: inline-flex;
    align-items: center;
    border: 1px solid var(--line-2);
    border-radius: var(--radius);
    width: fit-content;
}

.stepper__btn {
    width: 36px;
    height: 36px;
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    color: var(--ink);
}

.stepper__value {
    width: 36px;
    text-align: center;
    font-size: 14px;
}
</style>
