<template>
  <div class="min-h-screen bg-surface relative">
    <header class="fixed top-0 left-0 right-0 mx-auto max-w-md w-full z-50 bg-surface pt-safe md:top-8">
      <div class="h-16 flex justify-between items-center px-container-margin border-b border-outline-variant">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">restaurant</span>
          <h1 class="font-headline-md text-headline-md text-on-surface tracking-tight">點餐</h1>
        </div>
        <span class="font-label-lg text-label-lg px-3 py-1.5 bg-surface-container-high text-on-surface-variant rounded-full">{{ dineLabel }}</span>
      </div>
    </header>

    <main class="pt-[calc(4rem+env(safe-area-inset-top,0px))] pb-[calc(110px+env(safe-area-inset-bottom,20px))]">
      <div class="sticky top-[calc(4rem+env(safe-area-inset-top,0px))] md:top-[6rem] z-40 bg-surface/95 backdrop-blur-sm px-container-margin pt-4 pb-2 border-b border-surface-variant">
        <div class="flex overflow-x-auto gap-6 hide-scrollbar snap-x">
          <button type="button" class="snap-start whitespace-nowrap pb-2 font-label-lg text-label-lg transition-colors" :class="activeCategory === '' ? 'text-primary font-bold border-b-2 border-primary' : 'text-on-surface-variant font-medium border-b-2 border-transparent hover:text-on-surface'" @click="selectCategory('')">全部</button>
          <button v-for="c in categories" :key="c" type="button" class="snap-start whitespace-nowrap pb-2 font-label-lg text-label-lg transition-colors" :class="activeCategory === c ? 'text-primary font-bold border-b-2 border-primary' : 'text-on-surface-variant font-medium border-b-2 border-transparent hover:text-on-surface'" @click="selectCategory(c)">{{ c }}</button>
        </div>
      </div>

      <div class="px-container-margin py-6 flex flex-col gap-6">
        <div v-if="loading" class="py-16 text-center font-body-md text-body-md text-on-surface-variant">載入中...</div>
        <div v-else-if="!visibleProducts.length" class="py-16 text-center font-body-md text-body-md text-on-surface-variant">此分類暫無餐點</div>
        <ProductCard v-for="p in visibleProducts" :key="p.id" :product="p" />
      </div>
    </main>

    <div class="fixed bottom-[calc(env(safe-area-inset-bottom,20px)+14px)] md:bottom-[46px] max-w-[calc(448px-32px)] w-[calc(100%-32px)] left-1/2 -translate-x-1/2 z-40 bg-inverse-surface rounded-xl shadow-[0px_4px_20px_rgba(0,0,0,0.15)] flex justify-between items-center p-3 pl-5 border border-white/10">
      <div class="flex flex-col">
        <span class="font-body-md text-body-md text-inverse-on-surface/80">{{ count }} 項餐點</span>
        <span class="font-price-display text-price-display text-inverse-on-surface">NT$ {{ total.toLocaleString() }}</span>
      </div>
      <button type="button" class="bg-primary text-on-primary px-6 py-3 rounded-lg font-label-lg text-label-lg flex items-center gap-2 transition-all active:scale-95 disabled:opacity-40 disabled:pointer-events-none" :class="count > 0 ? 'hover:bg-primary/90' : ''" :disabled="count === 0" @click="goOrder">
        查看訂單
        <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCatalogStore } from '../store/catalog.js'
import { useOrderStore } from '../store/order.js'
import { useToastStore } from '../store/toast.js'
import { api } from '../api/index.js'
import ProductCard from '../components/ProductCard.vue'

const route = useRoute()
const router = useRouter()
const catalog = useCatalogStore()
const orderStore = useOrderStore()
const toastStore = useToastStore()

const activeCategory = ref('')
const categories = computed(() => catalog.categories)
const loading = computed(() => catalog.loading)
const visibleProducts = computed(() => {
  const all = catalog.allProducts
  if (!activeCategory.value) return all
  return all.filter(p => p.category === activeCategory.value)
})

const count = computed(() => orderStore.count)
const total = computed(() => orderStore.items.reduce((s, i) => s + i.price * i.quantity, 0))
const dineLabel = computed(() => {
  if (orderStore.orderType === 'takeout') return '外帶'
  if (orderStore.tableNumber > 0) return `桌號 ${orderStore.tableNumber}`
  return '內用'
})

function selectCategory(c) {
  activeCategory.value = c
}

function goOrder() {
  router.push('/order')
}

onMounted(async () => {
  await catalog.init()
  const removed = orderStore.pruneInvalid(catalog.allProducts)
  if (removed > 0) {
    toastStore.error(`已移除 ${removed} 項已下架或失效的餐點`)
  }
  const takeout = route.query.takeout || route.query.mode === 'takeout'
  const table = Number(route.query.table)
  if (takeout) {
    orderStore.setDine(0, 'takeout')
  } else if (table > 0) {
    orderStore.setDine(table, 'dine_in')
  } else if (orderStore.tableNumber > 0) {
    orderStore.setDine(orderStore.tableNumber, 'dine_in')
  } else {
    const res = await api.availableTable()
    const free = Number(res.data?.table_number) || 0
    orderStore.setDine(free, 'dine_in')
  }
})
</script>