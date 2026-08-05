<template>
  <div>
    <h1 style="margin-bottom:20px;">所有商品</h1>
    <div class="toolbar">
      <input v-model="q" type="text" placeholder="搜尋商品..." class="search-input" @input="onSearch" />
      <div class="cat-tabs">
        <button class="cat-tab" :class="{ active: !category }" @click="selectCategory('')">全部</button>
        <button v-for="c in categories" :key="c" class="cat-tab" :class="{ active: category === c }" @click="selectCategory(c)">{{ c }}</button>
      </div>
    </div>
    <div v-if="loading" style="text-align:center;padding:60px;color:#888;">載入中...</div>
    <div v-else-if="!products.length" style="text-align:center;padding:60px;color:#888;">尚無符合的商品</div>
    <div v-else>
      <div class="product-grid">
        <div v-for="p in products" :key="p.id" class="product-card">
          <router-link :to="`/products/${p.id}`">
            <div class="product-img">
              <img v-if="p.image" :src="p.image" :alt="p.name" />
              <div v-else class="no-img"><i class="fas fa-box"></i></div>
            </div>
            <div class="product-info">
              <h3>{{ p.name }}</h3>
              <div class="price">
                <span v-if="p.list_price" class="old-price">NT$ {{ p.list_price.toLocaleString() }}</span>
                <span class="sale-price">NT$ {{ p.price.toLocaleString() }}</span>
              </div>
            </div>
          </router-link>
          <button class="btn btn-primary" style="width:100%;border-radius:0 0 10px 10px;" @click="$emit('add-to-cart', p)">
            <i class="fas fa-cart-plus"></i> 加入購物車
          </button>
        </div>
      </div>
      <div v-if="totalPages > 1" class="pagination">
        <button :disabled="page <= 1" @click="goPage(page - 1)"><i class="fas fa-chevron-left"></i></button>
        <span>{{ page }} / {{ totalPages }}</span>
        <button :disabled="page >= totalPages" @click="goPage(page + 1)"><i class="fas fa-chevron-right"></i></button>
      </div>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'
export default {
  data() { return { products: [], categories: [], q: '', category: '', page: 1, totalPages: 1, loading: true, searchTimer: null } },
  methods: {
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

<style scoped>
.toolbar { margin-bottom: 20px; }
.search-input { width: 100%; max-width: 360px; padding: 10px 14px; border: 1px solid #d0d5dd; border-radius: 8px; font-size: 14px; outline: none; margin-bottom: 12px; }
.search-input:focus { border-color: #4CAF50; }
.cat-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
.cat-tab { padding: 6px 16px; border-radius: 20px; border: 1px solid #e0e0e0; background: #fff; color: #666; font-size: 13px; cursor: pointer; transition: all 0.2s; }
.cat-tab:hover { border-color: #4CAF50; color: #4CAF50; }
.cat-tab.active { background: #4CAF50; border-color: #4CAF50; color: #fff; }
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
.product-card { background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.06); transition: transform 0.2s, box-shadow 0.2s; }
.product-card:hover { transform: translateY(-3px); box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
.product-card a { text-decoration: none; color: inherit; display: block; }
.product-img { height: 200px; background: #f9f9f9; display: flex; align-items: center; justify-content: center; }
.product-img img { width: 100%; height: 100%; object-fit: cover; }
.no-img { font-size: 48px; color: #ccc; }
.product-info { padding: 16px; }
.product-info h3 { font-size: 15px; margin-bottom: 8px; }
.price { display: flex; gap: 8px; align-items: baseline; margin-bottom: 8px; }
.old-price { font-size: 13px; color: #aaa; text-decoration: line-through; }
.sale-price { font-size: 18px; font-weight: 700; color: #e44d26; }
.pagination { display: flex; justify-content: center; align-items: center; gap: 16px; margin-top: 28px; }
.pagination button { width: 38px; height: 38px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fff; color: #4CAF50; cursor: pointer; }
.pagination button:disabled { opacity: 0.4; cursor: not-allowed; }
.pagination span { font-size: 14px; color: #666; }
</style>
