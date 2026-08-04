<template>
  <div v-if="loading" style="text-align:center;padding:60px;color:#888;">載入中...</div>
  <div v-else-if="!p" style="text-align:center;padding:60px;color:#888;">商品不存在</div>
  <div v-else class="detail">
    <div class="detail-img">
      <img v-if="p.image" :src="p.image" :alt="p.name" />
      <div v-else class="no-img"><i class="fas fa-box"></i></div>
    </div>
    <div class="detail-info">
      <h1>{{ p.name }}</h1>
      <div class="price">
        <span v-if="p.list_price" class="old-price">NT$ {{ p.list_price.toLocaleString() }}</span>
        <span class="sale-price">NT$ {{ p.price.toLocaleString() }}</span>
      </div>
      <p class="stock" :class="p.stock > 0 ? 'in' : 'out'">
        <i :class="p.stock > 0 ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
        {{ p.stock > 0 ? '有庫存' : '完售' }}
      </p>
      <p class="desc">{{ p.description || '尚無描述' }}</p>
      <button class="btn btn-primary" @click="$emit('add-to-cart', p)" :disabled="!p.stock">
        <i class="fas fa-cart-plus"></i> 加入購物車
      </button>
      <router-link to="/" class="btn btn-default" style="margin-left:10px;" @click.prevent="goBack"><i class="fas fa-arrow-left"></i> 返回</router-link>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'
export default {
  data() { return { p: null, loading: true } },
  methods: {
    goBack() {
      if (window.history.length > 1) this.$router.back()
      else this.$router.push('/')
    },
  },
  async created() {
    const id = parseInt(this.$route.params.id)
    const res = await api.product(id)
    if (res.success) this.p = res.data
    this.loading = false
  },
}
</script>

<style scoped>
.detail { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
.detail-img { background: #f9f9f9; border-radius: 10px; display: flex; align-items: center; justify-content: center; min-height: 400px; }
.detail-img img { width: 100%; max-height: 500px; object-fit: cover; border-radius: 10px; }
.no-img { font-size: 64px; color: #ccc; }
.detail-info h1 { font-size: 24px; margin-bottom: 16px; }
.price { margin-bottom: 12px; }
.old-price { font-size: 16px; color: #aaa; text-decoration: line-through; margin-right: 10px; }
.sale-price { font-size: 28px; font-weight: 700; color: #e44d26; }
.stock { margin-bottom: 16px; font-size: 14px; }
.stock.in { color: #2e7d32; }
.stock.out { color: #c62828; }
.desc { color: #666; line-height: 1.7; margin-bottom: 24px; }

@media (max-width: 768px) {
  .detail { grid-template-columns: 1fr; gap: 24px; }
  .detail-img { min-height: 260px; }
}
</style>
