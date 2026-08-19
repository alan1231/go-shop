<template>
  <section class="product-page">
    <div v-if="loading" class="state-card glass-card">
      <span class="loader loader-lg"></span>
      <span>載入商品中</span>
    </div>

    <div v-else-if="!p" class="state-card glass-card">
      <span class="material-symbols-outlined state-icon">inventory_2</span>
      <h1>商品不存在</h1>
      <router-link to="/" class="secondary-button">返回首頁</router-link>
    </div>

    <template v-else>
      <button class="back-button" type="button" @click="goBack">
        <span class="material-symbols-outlined">arrow_back</span>
        返回商品列表
      </button>

      <div class="product-hero">
        <div class="image-panel glass-card">
          <img v-if="p.image" :src="p.image" :alt="p.name" />
          <span v-else class="material-symbols-outlined no-image">inventory_2</span>
        </div>

        <div class="product-info glass-card">
          <span v-if="p.category" class="category">{{ p.category }}</span>
          <h1>{{ p.name }}</h1>
          <div class="price-row">
            <span class="sale-price">NT$ {{ money(p.price) }}</span>
            <span v-if="p.list_price" class="list-price">NT$ {{ money(p.list_price) }}</span>
          </div>
          <div class="quick-description">{{ p.description || '此商品尚未提供詳細說明。' }}</div>
          <div class="actions">
            <button class="cart-button" type="button" @click="joinCart">
              <span class="material-symbols-outlined">add_shopping_cart</span>
              加入購物車
            </button>
            <button class="secondary-button" type="button" @click="goBack">繼續購物</button>
          </div>
        </div>
      </div>

      <section class="description-panel glass-card">
        <div class="section-heading">
          <div>
            <span class="eyebrow">PRODUCT DETAILS</span>
            <h2>產品說明</h2>
          </div>
          <span class="material-symbols-outlined">description</span>
        </div>
        <p>{{ p.description || '此商品尚未提供詳細說明。' }}</p>
        <dl class="product-meta">
          <div><dt>商品編號</dt><dd>#{{ p.id }}</dd></div>
          <div><dt>商品分類</dt><dd>{{ p.category || '未分類' }}</dd></div>
        </dl>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCatalogStore } from '../store/catalog.js'
import { useCartStore } from '../store/cart.js'
import { useToastStore } from '../store/toast.js'
import { money } from '../utils/format.js'

const route = useRoute()
const router = useRouter()
const catalog = useCatalogStore()
const cartStore = useCartStore()
const toastStore = useToastStore()

const p = computed(() => catalog.detail)
const loading = computed(() => catalog.detailLoading)

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push('/')
}

async function joinCart() {
  if (!p.value) return
  const r = await cartStore.add(p.value)
  if (r.ok) toastStore.success(r.message)
  else toastStore.error(r.message)
}

onMounted(() => catalog.loadDetail(parseInt(route.params.id)))
</script>

<style scoped>
.product-page {
  position: relative;
  max-width: 1080px;
  min-height: 65vh;
  margin: 0 auto;
  padding-bottom: 48px;
}
.back-button {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 18px;
  padding: 0;
  border: 0;
  background: transparent;
  color: var(--shop-text-muted);
  font: inherit;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: color .2s;
}
.back-button:hover { color: var(--shop-primary); }
.back-button .material-symbols-outlined { font-size: 19px; }
.product-hero {
  display: grid;
  gap: 28px;
  margin-bottom: 24px;
}
.image-panel,
.product-info,
.description-panel,
.state-card {
  border-radius: 16px;
  box-shadow: 0 16px 40px rgba(0, 0, 0, .2);
}
.image-panel {
  display: grid;
  min-height: 340px;
  place-items: center;
  background: var(--shop-surface-lowest);
  overflow: hidden;
}
.image-panel img {
  width: 100%;
  height: 100%;
  max-height: 580px;
  object-fit: cover;
}
.no-image { color: var(--shop-text-muted); font-size: 70px; }
.product-info {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: clamp(24px, 5vw, 44px);
}
.category {
  align-self: flex-start;
  margin-bottom: 14px;
  padding: 7px 11px;
  border: 1px solid color-mix(in srgb, var(--shop-primary) 28%, transparent);
  border-radius: 999px;
  background: color-mix(in srgb, var(--shop-primary) 9%, transparent);
  color: var(--shop-primary);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .06em;
}
.product-info h1 {
  margin: 0 0 20px;
  color: var(--shop-text);
  font-family: 'Sora', sans-serif;
  font-size: clamp(28px, 5vw, 42px);
  line-height: 1.15;
  letter-spacing: -.04em;
}
.price-row {
  display: flex;
  align-items: baseline;
  gap: 12px;
  margin-bottom: 16px;
}
.sale-price {
  color: var(--shop-primary);
  font-family: 'Sora', sans-serif;
  font-size: clamp(25px, 4vw, 34px);
  font-weight: 800;
}
.list-price {
  color: var(--shop-outline);
  font-size: 15px;
  text-decoration: line-through;
}
.quick-description {
  display: -webkit-box;
  margin-bottom: 26px;
  color: var(--shop-text-muted);
  font-size: 15px;
  line-height: 1.75;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
}
.actions { display: flex; gap: 10px; }
.cart-button,
.secondary-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 46px;
  padding: 11px 18px;
  border-radius: 9px;
  font: inherit;
  font-size: 13px;
  font-weight: 800;
  text-decoration: none;
  cursor: pointer;
  transition: border-color .2s, background .2s, color .2s, transform .2s;
}
.cart-button {
  flex: 1;
  border: 1px solid var(--shop-primary);
  background: var(--shop-primary);
  color: var(--shop-on-primary);
}
.cart-button:hover:not(:disabled) { background: var(--shop-primary-strong); transform: translateY(-1px); }
.cart-button:disabled { cursor: not-allowed; opacity: .45; }
.secondary-button {
  border: 1px solid var(--shop-border);
  background: var(--shop-surface-highest);
  color: var(--shop-text);
}
.secondary-button:hover { border-color: var(--shop-primary); color: var(--shop-primary); }
.description-panel { padding: clamp(24px, 5vw, 40px); }
.section-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 20px;
  padding-bottom: 18px;
  border-bottom: 1px solid var(--shop-border);
}
.eyebrow {
  display: block;
  margin-bottom: 6px;
  color: var(--shop-primary);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: .14em;
}
.section-heading h2 {
  margin: 0;
  color: var(--shop-text);
  font-family: 'Sora', sans-serif;
  font-size: 24px;
}
.section-heading > .material-symbols-outlined { color: var(--shop-primary); font-size: 34px; }
.description-panel > p {
  margin: 0;
  color: var(--shop-text-muted);
  font-size: 16px;
  line-height: 1.9;
  white-space: pre-line;
}
.product-meta {
  display: grid;
  gap: 12px;
  margin: 28px 0 0;
  padding-top: 22px;
  border-top: 1px solid var(--shop-border);
}
.product-meta > div { display: flex; justify-content: space-between; gap: 16px; }
.product-meta dt { color: var(--shop-text-muted); font-size: 12px; }
.product-meta dd { margin: 0; color: var(--shop-text); font-size: 13px; font-weight: 700; }
.state-card {
  display: grid;
  min-height: 300px;
  padding: 40px 20px;
  place-items: center;
  align-content: center;
  gap: 12px;
  color: var(--shop-text-muted);
  text-align: center;
}
.state-card h1 { margin: 0; color: var(--shop-text); font-size: 22px; }
.state-icon { color: var(--shop-primary); font-size: 50px; }
@media (min-width: 820px) {
  .product-hero { grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr); }
  .product-meta { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .product-meta > div { display: grid; gap: 5px; }
}
@media (max-width: 520px) {
  .image-panel { min-height: 280px; }
  .actions { flex-direction: column; }
  .actions > * { width: 100%; }
}
</style>
