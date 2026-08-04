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
        <div v-for="item in cart" :key="item.product_id" class="cart-item">
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
        <h3>收件資訊</h3>
        <div class="form-group">
          <label>收件人姓名</label>
          <input type="text" v-model="receiver.name" placeholder="收件人姓名" />
        </div>
        <div class="form-group">
          <label>手機號碼</label>
          <input type="tel" v-model="receiver.phone" placeholder="0912345678" />
        </div>
        <div class="form-group">
          <label>收件地址</label>
          <input type="text" v-model="receiver.address" placeholder="收件地址" />
        </div>
        <div class="form-group">
          <label>備註</label>
          <textarea v-model="remark" rows="3" placeholder="訂單備註（選填）"></textarea>
        </div>
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
import { cartStore } from '../store/cart.js'
import { toastStore } from '../store/toast.js'
export default {
  data() { return { ordering: false, receiver: { name: '', phone: '', address: '' }, remark: '' } },
  computed: {
    cart() { return cartStore.items },
    totalItems() { return cartStore.count },
    totalPrice() { return cartStore.items.reduce((s, i) => s + i.price * i.quantity, 0) },
  },
  methods: {
    changeQty(i, delta) {
      const r = cartStore.changeQty(i, delta)
      if (!r.ok && r.message) toastStore.error(r.message)
    },
    removeItem(i) {
      cartStore.remove(i)
    },
    async checkout() {
      const userRes = await api.me()
      if (!userRes.success) {
        this.$router.push('/login?redirect=/cart')
        return
      }
      if (!this.receiver.name || !this.receiver.phone || !this.receiver.address) {
        toastStore.error('請填寫完整的收件資訊')
        return
      }
      this.ordering = true
      const items = cartStore.items.map(i => ({ product_id: i.product_id, quantity: i.quantity }))
      const res = await api.createOrder(items, this.receiver, this.remark)
      if (res.success) {
        cartStore.clear()
        toastStore.success('訂單已建立！')
        this.$router.push(`/orders/${res.data.order_id}`)
      } else {
        toastStore.error(res.message)
      }
      this.ordering = false
    },
  },
  async created() {
    const userRes = await api.me()
    if (userRes.success) {
      const u = userRes.data
      this.receiver = {
        name: u.username || '',
        phone: u.phone || '',
        address: u.address || '',
      }
    }
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
.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: #666; margin-bottom: 4px; }
.form-group input { width: 100%; padding: 8px 10px; border: 1px solid #d0d5dd; border-radius: 6px; font-size: 13px; outline: none; }
.form-group input:focus { border-color: #4CAF50; }
.form-group textarea { width: 100%; padding: 8px 10px; border: 1px solid #d0d5dd; border-radius: 6px; font-size: 13px; outline: none; resize: vertical; }
.form-group textarea:focus { border-color: #4CAF50; }
.summary-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
.summary-row.total { border-top: 1px solid #eee; margin-top: 8px; padding-top: 16px; font-size: 18px; font-weight: 700; }

@media (max-width: 768px) {
  .cart-layout { grid-template-columns: 1fr; }
  .cart-item { flex-wrap: wrap; gap: 10px; }
  .item-info { flex: 1 1 100%; }
  .item-total { min-width: auto; }
}
</style>
