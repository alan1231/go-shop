<template>
  <div>
    <h1 style="margin-bottom:24px;">購物車</h1>
    <div v-if="!cart.length" style="text-align:center;padding:60px;color:#888;">
      <i class="fas fa-shopping-cart" style="font-size:48px;color:#ccc;margin-bottom:16px;"></i>
      <p>購物車是空的</p>
      <router-link to="/" class="btn btn-primary" style="margin-top:16px;">去購物</router-link>
    </div>
    <div v-else class="cart-layout">
      <div class="cart-items">
        <div v-for="(item, i) in cart" :key="i" class="cart-item">
          <div class="item-info">
            <strong>{{ item.name }}</strong>
            <span class="item-price">NT$ {{ item.price.toLocaleString() }}</span>
          </div>
          <div class="item-qty">
            <button @click="changeQty(i, -1)" class="qty-btn">−</button>
            <span>{{ item.quantity }}</span>
            <button @click="changeQty(i, 1)" class="qty-btn">+</button>
          </div>
          <div class="item-total">NT$ {{ (item.price * item.quantity).toLocaleString() }}</div>
          <button @click="removeItem(i)" class="qty-btn" style="color:#f44336;"><i class="fas fa-trash"></i></button>
        </div>
      </div>
      <div class="cart-summary">
        <h3>訂單摘要</h3>
        <div class="summary-row"><span>商品數量</span><span>{{ totalItems }} 件</span></div>
        <div class="summary-row total"><span>總金額</span><span>NT$ {{ totalPrice.toLocaleString() }}</span></div>
        <button class="btn btn-primary" style="width:100%;margin-top:16px;" @click="checkout" :disabled="ordering">
          {{ ordering ? '處理中...' : '結帳' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'
export default {
  data() { return { ordering: false } },
  computed: {
    cart() { return JSON.parse(localStorage.getItem('cart') || '[]') },
    totalItems() { return this.cart.reduce((s, i) => s + i.quantity, 0) },
    totalPrice() { return this.cart.reduce((s, i) => s + i.price * i.quantity, 0) },
  },
  methods: {
    save(cart) { localStorage.setItem('cart', JSON.stringify(cart)) },
    changeQty(i, delta) {
      const cart = this.cart
      cart[i].quantity = Math.max(1, cart[i].quantity + delta)
      this.save(cart)
      this.$forceUpdate()
    },
    removeItem(i) {
      const cart = this.cart
      cart.splice(i, 1)
      this.save(cart)
      this.$forceUpdate()
    },
    async checkout() {
      const userRes = await api.me()
      if (!userRes.success) {
        this.$router.push('/login')
        return
      }
      this.ordering = true
      const items = this.cart.map(i => ({ product_id: i.product_id, quantity: i.quantity }))
      const res = await api.createOrder(items)
      if (res.success) {
        localStorage.removeItem('cart')
        this.$router.push(`/orders/${res.data.order_id}`)
      } else {
        alert(res.message)
      }
      this.ordering = false
    },
  },
}
</script>

<style scoped>
.cart-layout { display: grid; grid-template-columns: 1fr 300px; gap: 24px; align-items: start; }
.cart-item { display: flex; align-items: center; gap: 16px; background: #fff; padding: 16px; border-radius: 8px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
.item-info { flex: 1; }
.item-info strong { display: block; margin-bottom: 4px; }
.item-price { font-size: 13px; color: #888; }
.item-qty { display: flex; align-items: center; gap: 8px; }
.qty-btn { width: 32px; height: 32px; border: 1px solid #ddd; border-radius: 6px; background: #fff; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; }
.qty-btn:hover { background: #f5f5f5; }
.item-total { font-weight: 600; min-width: 100px; text-align: right; }
.cart-summary { background: #fff; padding: 24px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
.cart-summary h3 { margin-bottom: 16px; font-size: 16px; }
.summary-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
.summary-row.total { border-top: 1px solid #eee; margin-top: 8px; padding-top: 16px; font-size: 18px; font-weight: 700; }
</style>
