<template>
  <article class="product-card">
    <router-link :to="`/products/${product.id}`" class="product-link">
      <div class="product-image">
        <img v-if="product.image" :src="product.image" :alt="product.name" />
        <span v-else class="material-symbols-outlined placeholder-icon">inventory_2</span>
        <span v-if="discount" class="product-badge">-{{ discount }}%</span>
      </div>
      <small>{{ product.category }}</small>
      <h3>{{ product.name }}</h3>
      <div class="product-price">
        <strong>NT$ {{ money(product.price) }}</strong>
        <del v-if="product.list_price">NT$ {{ money(product.list_price) }}</del>
      </div>
    </router-link>
    <button type="button" @click="addToCart">
      <span class="material-symbols-outlined">shopping_cart</span>
      加入訂單
    </button>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { money } from '../utils/format.js'
import { useCartStore } from '../store/cart.js'
import { useToastStore } from '../store/toast.js'

const props = defineProps({
  product: { type: Object, required: true },
})

const cartStore = useCartStore()
const toastStore = useToastStore()

const discount = computed(() => {
  if (Number(props.product.list_price) <= Number(props.product.price)) return 0
  return Math.round((1 - Number(props.product.price) / Number(props.product.list_price)) * 100)
})

async function addToCart() {
  const r = await cartStore.add(props.product)
  if (r.ok) toastStore.success(r.message)
  else toastStore.error(r.message)
}
</script>

<style scoped>
.product-card { display: flex; min-width: 0; height: 300px; flex-direction: column; padding: 6px 10px; border: 1px solid var(--shop-border); border-radius: 14px; background: var(--shop-glass-card); transition: border-color .2s, background .2s, transform .2s; }
.product-card:hover { border-color: color-mix(in srgb, var(--shop-primary) 40%, transparent); background: var(--shop-surface-high); transform: translateY(-2px); }
.product-link { display: flex; flex: 1; flex-direction: column; color: var(--shop-text); text-decoration: none; }
.product-image { position: relative; display: grid; width: 100%; height: 135px; flex: 0 0 135px; margin-bottom: 5px; place-items: center; border-radius: 10px; background: var(--shop-surface-lowest); overflow: hidden; }
.product-image img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s ease; }
.product-card:hover .product-image img { transform: scale(1.04); }
.placeholder-icon { color: var(--shop-text-muted); font-size: 36px; }
.product-badge { position: absolute; top: 8px; left: 8px; padding: 6px 8px; border-radius: 999px; background: var(--shop-error); color: #3b0905; font-size: 11px; font-weight: 800; }
.product-badge.sold-out { background: var(--shop-surface-highest); color: var(--shop-text-muted); }
.product-link > small { color: var(--shop-text-muted); font-size: 12px; }
.product-link h3 { margin: 3px 0 5px; font-size: 16px; line-height: 1.35; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.product-price { display: flex; min-height: 20px; align-items: baseline; gap: 8px; }
.product-price strong { color: var(--shop-primary); font-family: 'JetBrains Mono', monospace; font-size: 15px; }
.product-price del { color: var(--shop-text-muted); font-size: 11px; }
.product-card > button { display: flex; width: 100%; min-height: 42px; align-items: center; justify-content: center; gap: 7px; margin-top: 5px; border: 1px solid var(--shop-border); border-radius: 9px; background: var(--shop-surface-highest); color: var(--shop-text); font: inherit; font-size: 14px; font-weight: 700; cursor: pointer; transition: border-color .2s, background .2s, color .2s; }
.product-card > button:hover:not(:disabled) { border-color: var(--shop-primary); background: color-mix(in srgb, var(--shop-primary) 12%, var(--shop-surface-highest)); color: var(--shop-primary); }
.product-card > button:disabled { cursor: not-allowed; opacity: .5; }
.product-card > button .material-symbols-outlined { font-size: 18px; }
@media (max-width: 420px) {
  .product-link h3 { font-size: 15px; }
  .product-price { gap: 5px; }
  .product-price strong { font-size: 13px; }
  .product-price del { font-size: 9px; }
  .product-card > button { font-size: 13px; }
}
</style>
