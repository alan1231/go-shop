<template>
  <div class="min-h-screen bg-background text-on-background relative overflow-x-hidden">
    <main class="pt-4 px-margin-mobile max-w-container-max mx-auto">
      <section v-if="diningContext" class="dining-banner" aria-label="用餐方式">
        <span class="material-symbols-outlined">{{ diningContext.icon }}</span>
        <div>
          <strong>{{ diningContext.label }}</strong>
          <small>已記錄，結帳時會帶入訂單</small>
        </div>
      </section>

      <section v-if="featuredProduct && showDiscovery" class="home-hero">
        <div class="hero-copy">
          <span class="hero-kicker">CURATED TECH · 2026</span>
          <h1>探索讓日常更有趣的科技選物</h1>
          <p>從影音、創作到行動生活，精選實用又有風格的熱門商品。</p>
          <div class="hero-actions">
            <router-link :to="`/products/${featuredProduct.id}`" class="hero-primary">選購本週精選</router-link>
            <button type="button" class="hero-secondary" @click="scrollToProducts">瀏覽全部商品</button>
          </div>
        </div>
        <router-link :to="`/products/${featuredProduct.id}`" class="hero-product">
          <span class="hero-badge">本週精選</span>
          <img v-if="featuredProduct.image" :src="featuredProduct.image" :alt="featuredProduct.name" />
          <div class="hero-product-copy">
            <small>{{ featuredProduct.category }}</small>
            <strong>{{ featuredProduct.name }}</strong>
            <span>NT$ {{ money(featuredProduct.price) }}</span>
          </div>
        </router-link>
      </section>

      <section class="trust-strip" aria-label="購物保障">
        <div><span class="material-symbols-outlined">local_shipping</span><strong>全館免運</strong><small>輕鬆送到家</small></div>
        <div><span class="material-symbols-outlined">verified_user</span><strong>安心付款</strong><small>安全交易保障</small></div>
        <div><span class="material-symbols-outlined">package_2</span><strong>快速出貨</strong><small>訂單即時追蹤</small></div>
        <div><span class="material-symbols-outlined">support_agent</span><strong>售後服務</strong><small>購物問題協助</small></div>
      </section>

      <section v-if="showDiscovery" class="discovery-section">
        <div class="section-heading">
          <div><span>SHOP BY CATEGORY</span><h2>精選分類</h2></div>
          <small>找到適合你的生活選物</small>
        </div>
        <div class="category-showcase">
          <button v-for="c in categories" :key="c" type="button" @click="selectCategory(c)">
            <span class="material-symbols-outlined">{{ categoryIcon(c) }}</span>
            <strong>{{ c }}</strong>
            <small>{{ categoryCount(c) }} 件商品</small>
          </button>
        </div>
      </section>

      <section v-if="saleProducts.length && showDiscovery" class="discovery-section sale-section">
        <div class="section-heading">
          <div><span>LIMITED OFFERS</span><h2>限時優惠</h2></div>
          <small>精選好物，優惠入手</small>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <ProductCard v-for="p in saleProducts" :key="p.id" :product="p" />
        </div>
      </section>

      <div ref="catalogEl" class="catalog-anchor"></div>
      <div class="mb-6 mt-2 relative">
        <div class="glass-card flex items-center px-4 py-3 rounded-xl focus-within:border-primary transition-colors duration-300 shadow-[inset_0_2px_4px_rgba(0,0,0,0.4)] focus-within:shadow-[0_0_15px_rgba(76,175,80,0.25)]">
          <span class="material-symbols-outlined text-on-surface-variant mr-3">search</span>
          <input ref="searchInput" v-model="q" type="text" placeholder="搜尋商品..." class="bg-transparent border-none outline-none flex-grow font-body-md text-body-md text-on-surface placeholder:text-on-surface-variant w-full focus:ring-0" @input="onSearch" />
        </div>
      </div>

      <div class="overflow-x-auto hide-scrollbar mb-8 -mx-margin-mobile px-margin-mobile flex gap-3">
        <button :class="catClass(category === '')" @click="selectCategory('')">全部</button>
        <button v-for="c in categories" :key="c" :class="catClass(category === c)" @click="selectCategory(c)">{{ c }}</button>
      </div>

      <div class="catalog-heading">
        <div><span>PRODUCT COLLECTION</span><h2>{{ catalogTitle }}</h2></div>
        <small v-if="!loading">第 {{ page }} 頁，共 {{ totalPages }} 頁</small>
      </div>

      <div v-if="loading" class="text-center py-16 text-on-surface-variant"><i class="fas fa-spinner fa-spin"></i> 載入中...</div>
      <div v-else-if="!products.length" class="text-center py-16 text-on-surface-variant">尚無符合的商品</div>
      <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <ProductCard v-for="p in products" :key="p.id" :product="p" />
      </div>

      <div v-if="totalPages > 1" class="flex justify-center items-center gap-4 mb-12">
        <button :disabled="page <= 1" class="w-10 h-10 rounded-full glass-card flex items-center justify-center text-on-surface-variant hover:text-primary hover:border-primary/50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" @click="goPage(page - 1)">
          <span class="material-symbols-outlined">chevron_left</span>
        </button>
        <span class="font-label-sm text-label-sm text-on-surface">第 {{ page }} / {{ totalPages }} 頁</span>
        <button :disabled="page >= totalPages" class="w-10 h-10 rounded-full glass-card flex items-center justify-center text-on-surface-variant hover:text-primary hover:border-primary/50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" @click="goPage(page + 1)">
          <span class="material-symbols-outlined">chevron_right</span>
        </button>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useCatalogStore } from '../store/catalog.js'
import { useCartStore } from '../store/cart.js'
import ProductCard from '../components/ProductCard.vue'
import { money } from '../utils/format.js'

const route = useRoute()
const catalog = useCatalogStore()
const cartStore = useCartStore()
const catalogEl = ref(null)
const searchInput = ref(null)

const q = computed({
  get: () => catalog.q,
  set: (v) => { catalog.q = v; catalog.onSearchInput() },
})
const products = computed(() => catalog.products)
const categories = computed(() => catalog.categories)
const category = computed(() => catalog.category)
const page = computed(() => catalog.page)
const totalPages = computed(() => catalog.totalPages)
const loading = computed(() => catalog.loading)
const showDiscovery = computed(() => catalog.showDiscovery)
const featuredProduct = computed(() => catalog.featuredProduct)
const saleProducts = computed(() => catalog.saleProducts)
const catalogTitle = computed(() => catalog.catalogTitle)

const diningContext = computed(() => {
  if (cartStore.orderType === 'takeout') return { icon: 'takeout_dining', label: '外帶點餐' }
  if (cartStore.tableNumber > 0) return { icon: 'table_restaurant', label: `${cartStore.tableNumber} 號桌點餐` }
  return null
})

function categoryCount(value) {
  return catalog.categoryCount(value)
}
function categoryIcon(value) {
  return catalog.categoryIcon(value)
}
function scrollToProducts() {
  catalogEl.value?.scrollIntoView({ behavior: 'smooth' })
}
function catClass(active) {
  return active
    ? 'whitespace-nowrap px-4 py-2 rounded-full font-label-sm text-label-sm bg-primary text-on-primary font-bold shadow-[0_0_15px_rgba(76,175,80,0.3)]'
    : 'whitespace-nowrap px-4 py-2 rounded-full font-label-sm text-label-sm glass-card text-on-surface-variant hover:border-primary/50 transition-colors'
}
function onSearch() {
  catalog.onSearchInput()
}
function selectCategory(c) {
  catalog.selectCategory(c)
}
function goPage(p) {
  catalog.goPage(p)
}

onMounted(async () => {
  catalog.init()
  const takeout = route.query.takeout === '1' || route.query.mode === 'takeout'
  const table = Number(route.query.table)
  if (takeout) {
    cartStore.setDine(0, 'takeout')
  } else if (table > 0) {
    cartStore.setDine(table, 'dine_in')
  }
})
</script>

<style scoped>
.home-hero { display: grid; gap: 24px; margin: 12px 0 24px; padding: clamp(24px, 5vw, 56px); border: 1px solid var(--shop-border); border-radius: 24px; background: radial-gradient(circle at 80% 20%, rgba(117, 255, 158, .18), transparent 34%), linear-gradient(135deg, var(--shop-surface-low), var(--shop-background)); overflow: hidden; }
.dining-banner { display: flex; align-items: center; gap: 14px; margin: 12px 0 4px; padding: 16px 18px; border: 1px solid var(--shop-primary); border-radius: 14px; background: color-mix(in srgb, var(--shop-primary) 10%, var(--shop-surface-low)); }
.dining-banner .material-symbols-outlined { color: var(--shop-primary); font-size: 26px; }
.dining-banner strong { display: block; color: var(--shop-text); font-size: 15px; }
.dining-banner small { color: var(--shop-text-muted); font-size: 11px; }
.hero-copy { align-self: center; }
.hero-kicker, .section-heading span, .catalog-heading span { color: var(--shop-primary); font-family: 'JetBrains Mono', monospace; font-size: 10px; font-weight: 700; letter-spacing: .16em; }
.hero-copy h1 { max-width: 650px; margin: 12px 0; color: var(--shop-text); font-family: 'Sora', sans-serif; font-size: clamp(32px, 7vw, 64px); line-height: 1.08; letter-spacing: -.05em; }
.hero-copy p { max-width: 560px; margin: 0; color: var(--shop-text-muted); font-size: 15px; line-height: 1.7; }
.hero-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 24px; }
.hero-primary, .hero-secondary { display: inline-flex; min-height: 46px; align-items: center; justify-content: center; padding: 0 20px; border-radius: 999px; font: inherit; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; }
.hero-primary { border: 1px solid var(--shop-primary); background: var(--shop-primary); color: var(--shop-on-primary); }
.hero-secondary { border: 1px solid var(--shop-border); background: var(--shop-surface-high); color: var(--shop-text); }
.hero-product { position: relative; display: block; min-height: 320px; border: 1px solid var(--shop-border); border-radius: 18px; background: var(--shop-surface-lowest); color: var(--shop-text); overflow: hidden; text-decoration: none; }
.hero-product img { width: 100%; height: 100%; min-height: 320px; object-fit: cover; transition: transform .45s ease; }
.hero-product:hover img { transform: scale(1.04); }
.hero-badge { position: absolute; top: 16px; left: 16px; z-index: 1; padding: 7px 11px; border-radius: 999px; background: var(--shop-primary); color: var(--shop-on-primary); font-size: 11px; font-weight: 800; }
.hero-product-copy { position: absolute; right: 14px; bottom: 14px; left: 14px; display: grid; gap: 3px; padding: 14px; border: 1px solid rgba(255,255,255,.13); border-radius: 12px; background: rgba(19,19,19,.82); backdrop-filter: blur(16px); }
.hero-product-copy small { color: var(--shop-text-muted); font-size: 10px; }
.hero-product-copy strong { font-size: 15px; }
.hero-product-copy span { color: var(--shop-primary); font-family: 'JetBrains Mono', monospace; font-size: 12px; }
.trust-strip { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1px; margin-bottom: 44px; border: 1px solid var(--shop-border); border-radius: 16px; background: var(--shop-border); overflow: hidden; }
.trust-strip > div { display: grid; grid-template-columns: 32px 1fr; padding: 18px; background: var(--shop-surface-low); }
.trust-strip .material-symbols-outlined { grid-row: 1 / 3; align-self: center; color: var(--shop-primary); font-size: 24px; }
.trust-strip strong { color: var(--shop-text); font-size: 13px; }
.trust-strip small { color: var(--shop-text-muted); font-size: 10px; }
.discovery-section { margin-bottom: 44px; }
.section-heading, .catalog-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
.section-heading h2, .catalog-heading h2 { margin: 4px 0 0; color: var(--shop-text); font-family: 'Sora', sans-serif; font-size: clamp(22px, 4vw, 30px); letter-spacing: -.03em; }
.section-heading > small, .catalog-heading > small { color: var(--shop-text-muted); font-size: 11px; }
.category-showcase { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.category-showcase button { display: grid; min-height: 120px; justify-items: start; padding: 18px; border: 1px solid var(--shop-border); border-radius: 14px; background: var(--shop-glass-card); color: var(--shop-text); text-align: left; cursor: pointer; transition: border-color .2s, background .2s, transform .2s; }
.category-showcase button:hover { border-color: var(--shop-primary); background: var(--shop-surface-high); transform: translateY(-2px); }
.category-showcase .material-symbols-outlined { color: var(--shop-primary); font-size: 28px; }
.category-showcase strong { align-self: end; font-size: 13px; }
.category-showcase small { color: var(--shop-text-muted); font-size: 10px; }
.catalog-anchor { scroll-margin-top: 90px; }
.catalog-heading { margin-top: 30px; }
@media (min-width: 700px) {
  .home-hero { grid-template-columns: minmax(0, 1.1fr) minmax(320px, .9fr); }
  .trust-strip { grid-template-columns: repeat(4, 1fr); }
  .category-showcase { grid-template-columns: repeat(4, 1fr); }
}
@media (max-width: 699px) {
  .home-hero { margin-top: 4px; }
  .hero-product { min-height: 280px; }
  .hero-product img { min-height: 280px; }
  .section-heading > small, .catalog-heading > small { display: none; }
}
</style>
