<template>
  <div class="min-h-screen bg-background text-on-background relative overflow-x-hidden">
    <main class="pt-4 px-margin-mobile max-w-container-max mx-auto">
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

      <div v-if="loading" class="text-center py-16 text-on-surface-variant"><i class="fas fa-spinner fa-spin"></i> 載入中...</div>
      <div v-else-if="!products.length" class="text-center py-16 text-on-surface-variant">尚無符合的商品</div>
      <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div v-for="p in products" :key="p.id" class="glass-card rounded-xl p-3 flex flex-col hover:bg-surface-container-high transition-all duration-300 group">
          <router-link :to="`/products/${p.id}`" class="block">
            <div class="relative w-full aspect-square rounded-lg overflow-hidden mb-3 bg-surface-container-lowest">
              <img v-if="p.image" :src="p.image" :alt="p.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
              <div v-else class="w-full h-full flex items-center justify-center text-on-surface-variant"><i class="fas fa-box"></i></div>
              <div v-if="p.list_price" class="absolute top-2 left-2 bg-error text-on-error font-label-sm text-[10px] px-2 py-1 rounded-full animate-pulse">特價</div>
              <div v-else-if="p.stock === 0" class="absolute top-2 left-2 bg-surface-container text-on-surface-variant font-label-sm text-[10px] px-2 py-1 rounded-full">完售</div>
            </div>
            <h3 class="font-title-md text-title-md text-on-surface truncate mb-1 text-[16px]">{{ p.name }}</h3>
            <div class="flex items-center gap-2 mb-3">
              <span class="font-label-sm text-label-sm text-primary">NT$ {{ Number(p.price).toLocaleString() }}</span>
              <span v-if="p.list_price" class="font-label-sm text-[10px] text-on-surface-variant line-through decoration-error/50">NT$ {{ Number(p.list_price).toLocaleString() }}</span>
            </div>
          </router-link>
          <button class="mt-auto w-full py-2 rounded-lg bg-surface-container-highest border border-white/10 flex items-center justify-center gap-2 hover:bg-primary/20 hover:border-primary transition-colors hover:text-primary active:scale-95 duration-200" @click="$emit('add-to-cart', p)">
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">shopping_cart</span>
          </button>
        </div>
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

<script>
import { api } from '../api/index.js'

export default {
  data() {
    return { products: [], categories: [], q: '', category: '', page: 1, totalPages: 1, loading: true, searchTimer: null }
  },
  methods: {
    catClass(active) {
      return active
        ? 'whitespace-nowrap px-4 py-2 rounded-full font-label-sm text-label-sm bg-primary text-on-primary font-bold shadow-[0_0_15px_rgba(76,175,80,0.3)]'
        : 'whitespace-nowrap px-4 py-2 rounded-full font-label-sm text-label-sm glass-card text-on-surface-variant hover:border-primary/50 transition-colors'
    },
    onSearch() {
      clearTimeout(this.searchTimer)
      this.searchTimer = setTimeout(() => { this.page = 1; this.loadProducts() }, 300)
    },
    selectCategory(c) {
      this.category = c
      this.page = 1
      this.loadProducts()
    },
    goPage(p) {
      if (p < 1 || p > this.totalPages) return
      this.page = p
      this.loadProducts()
    },
    async loadProducts() {
      this.loading = true
      const res = await api.products({ q: this.q, category: this.category, page: this.page })
      if (res.success) {
        this.products = res.data.items
        this.page = res.data.page
        this.totalPages = res.data.total_pages
      }
      this.loading = false
    },
  },
  async created() {
    const [pRes, cRes] = await Promise.all([api.products(), api.categories()])
    if (pRes.success) {
      this.products = pRes.data.items
      this.totalPages = pRes.data.total_pages
    }
    if (cRes.success) this.categories = cRes.data
    this.loading = false
  },
}
</script>
