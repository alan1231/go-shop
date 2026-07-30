<template>
  <div>
    <h1 style="margin-bottom:24px;">所有商品</h1>
    <div v-if="loading" style="text-align:center;padding:60px;color:#888;">載入中...</div>
    <div v-else-if="!products.length" style="text-align:center;padding:60px;color:#888;">尚無商品</div>
    <div v-else class="product-grid">
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
            <span class="stock-badge" :class="p.stock > 0 ? 'in' : 'out'">
              {{ p.stock > 0 ? '有庫存' : '完售' }}
            </span>
          </div>
        </router-link>
        <button class="btn btn-primary" style="width:100%;border-radius:0 0 10px 10px;" @click="$emit('add-to-cart', p)">
          <i class="fas fa-cart-plus"></i> 加入購物車
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'
export default {
  data() { return { products: [], loading: true } },
  async created() {
    const res = await api.products()
    if (res.success) this.products = res.data
    this.loading = false
  },
}
</script>

<style scoped>
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
.stock-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; }
.stock-badge.in { background: #e8f5e9; color: #2e7d32; }
.stock-badge.out { background: #ffebee; color: #c62828; }
</style>
