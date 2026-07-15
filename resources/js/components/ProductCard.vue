<script setup>
import { RouterLink } from 'vue-router'
import { formatPrice } from '@/utils/format'

defineProps({
    product: { type: Object, required: true },
})
</script>

<template>
    <RouterLink :to="`/products/${product.slug}`" class="product-card">
        <div class="product-card__image">
            <img v-if="product.primary_image" :src="product.primary_image" :alt="product.name" loading="lazy" />
            <div v-else class="product-card__placeholder muted mono">Tidak ada gambar</div>
        </div>
        <p class="eyebrow product-card__motif">{{ product.motif }}</p>
        <h3 class="product-card__name">{{ product.name }}</h3>
        <p class="price">{{ formatPrice(product.price) }}</p>
    </RouterLink>
</template>

<style scoped>
.product-card {
    display: block;
    text-decoration: none;
    color: var(--ink);
}

.product-card__image {
    aspect-ratio: 3 / 4;
    overflow: hidden;
    background: var(--paper-2);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    margin-bottom: 12px;
}

.product-card__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .product-card__image img {
    transform: scale(1.04);
}

.product-card__placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-card__motif {
    margin: 0 0 4px;
}

.product-card__name {
    margin: 0 0 6px;
    font-size: 15px;
    font-weight: 500;
    line-height: 1.35;
}
</style>
