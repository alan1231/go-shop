import { defineStore } from 'pinia'
import { api } from '../api/index.js'

export const useCatalogStore = defineStore('catalog', {
  state: () => ({
    categories: [],
    allProducts: [],
    products: [],
    q: '',
    category: '',
    page: 1,
    totalPages: 1,
    loading: true,
    detail: null,
    detailLoading: true,
    searchTimer: null,
  }),
  getters: {
    showDiscovery: state => !state.q && !state.category && state.page === 1,
    featuredProduct: state => state.allProducts[0] || state.products[0] || null,
    saleProducts: state => state.allProducts.filter(p => Number(p.list_price) > Number(p.price)).slice(0, 4),
    catalogTitle(state) {
      if (state.q) return `「${state.q}」的搜尋結果`
      if (state.category) return state.category
      return state.page === 1 ? '最新商品' : '更多商品'
    },
    categoryCount: state => (value) =>
      state.allProducts.filter(product => product.category === value).length,
  },
  actions: {
    categoryIcon(value) {
      const icons = { 手機: 'smartphone', 音訊設備: 'headphones', 電腦設備: 'computer', 影像設備: 'photo_camera', 穿戴與虛擬實境: 'view_in_ar', 食品: 'nutrition', 玩具: 'toys' }
      return icons[value] || 'category'
    },
    onSearchInput() {
      if (this.searchTimer) clearTimeout(this.searchTimer)
      this.searchTimer = setTimeout(() => {
        this.page = 1
        this.loadProducts()
      }, 300)
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
    async init() {
      const [cRes, allRes] = await Promise.all([api.categories(), api.products({ per_page: 100 })])
      if (cRes.success) this.categories = cRes.data
      if (allRes.success) {
        this.allProducts = allRes.data.items
        this.products = allRes.data.items.slice(0, 10)
        this.totalPages = Math.max(1, Math.ceil(allRes.data.total / 10))
      }
      this.loading = false
    },
    async loadDetail(id) {
      this.detail = null
      this.detailLoading = true
      const res = await api.product(id)
      if (res.success) this.detail = res.data
      this.detailLoading = false
      return res
    },
  },
})