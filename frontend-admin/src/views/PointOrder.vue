<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-plus-circle"></i> 新增訂單</h1>
    </div>

    <div v-if="msg" :class="'msg msg-' + msgType">{{ msg }}</div>

    <div class="pos-layout">
      <div class="pos-left card">
        <div class="pos-toolbar">
          <input v-model="q" placeholder="搜尋商品..." class="search-input" />
          <div class="chip-group">
            <button class="chip" :class="{ active: category === '' }" @click="setCategory('')">全部</button>
            <button v-for="c in categories" :key="c" class="chip" :class="{ active: category === c }" @click="setCategory(c)">{{ c }}</button>
          </div>
        </div>

        <div v-if="loading" class="pos-empty"><i class="fas fa-spinner fa-spin"></i> 載入中...</div>
        <div v-else-if="!visibleProducts.length" class="pos-empty">尚無上架商品</div>
        <div v-else class="pos-grid">
          <div
            v-for="p in visibleProducts"
            :key="p.id"
            class="pos-item"
            :class="{ selected: selected[p.id] }"
            @click="toggle(p)"
          >
            <div class="pos-item-img">
              <img v-if="p.image" :src="p.image" alt="" />
              <i v-else class="fas fa-utensils"></i>
            </div>
            <div class="pos-item-name">{{ p.name }}</div>
            <div class="pos-item-price">{{ fmt(p.price) }}</div>
          </div>
        </div>
      </div>

      <div class="pos-right card">
        <div class="pos-right-head">
          <div class="form-group" style="margin:0;">
            <label>桌號</label>
            <select v-model="tableNumber" style="width:110px;">
              <option :value="0">無</option>
              <option v-for="n in tableCount" :key="n" :value="n">{{ n }} 號桌</option>
            </select>
          </div>
          <div class="pos-total">
            <span>合計</span>
            <strong>{{ fmt(total) }}</strong>
          </div>
        </div>

        <div class="pos-cart" v-if="cartLines.length">
          <div v-for="line in cartLines" :key="line.id" class="cart-line">
            <div class="cart-name">{{ line.name }}</div>
            <div class="cart-qty">
              <button @click="decr(line.id)">-</button>
              <span>{{ line.quantity }}</span>
              <button @click="incr(line.id)">+</button>
            </div>
            <div class="cart-sub">{{ fmt(line.price * line.quantity) }}</div>
          </div>
        </div>
        <div v-else class="pos-cart-empty">
          <i class="fas fa-cart-plus"></i>
          <p>點選左側商品加入訂單</p>
        </div>

        <div class="pos-actions">
          <button class="btn btn-default" :disabled="!cartLines.length || loading" @click="submit(false)">
            <i class="fas fa-file-invoice"></i> 僅建立訂單
          </button>
          <button class="btn btn-primary" :disabled="!cartLines.length || loading" @click="submit(true)">
            <i class="fas fa-coins"></i> {{ loading ? '處理中...' : '現金結帳' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'

export default {
  name: 'PointOrderView',
  data() {
    return {
      products: [],
      categories: [],
      category: '',
      q: '',
      selected: {},
      cartLines: [],
      tableNumber: 0,
      tableCount: 0,
      loading: true,
      msg: '',
      msgType: 'success',
    }
  },
  computed: {
    visibleProducts() {
      const q = this.q.trim().toLowerCase()
      return this.products.filter((p) => {
        if (this.category && p.category !== this.category) return false
        if (q && !String(p.name).toLowerCase().includes(q)) return false
        return true
      })
    },
    total() {
      return this.cartLines.reduce((s, l) => s + l.price * l.quantity, 0)
    },
  },
  async created() {
    const [pRes, cRes, sRes] = await Promise.all([api.products({ per_page: 500 }), api.categories(), api.settings()])
    if (pRes.success) this.products = (pRes.data.items || []).filter((p) => p.status === 'active')
    if (cRes.success) this.categories = cRes.data || []
    if (sRes.success) this.tableCount = sRes.data.table_count || 0
    this.loading = false
  },
  methods: {
    fmt(n) {
      return 'NT$ ' + Number(n).toLocaleString()
    },
    setCategory(c) {
      this.category = c
    },
    toggle(p) {
      if (this.selected[p.id]) {
        this.decr(p.id)
      } else {
        this.selected[p.id] = true
        this.cartLines.push({ id: p.id, name: p.name, price: p.price, quantity: 1 })
      }
    },
    incr(id) {
      const line = this.cartLines.find((l) => l.id === id)
      if (line) line.quantity += 1
    },
    decr(id) {
      const line = this.cartLines.find((l) => l.id === id)
      if (!line) return
      line.quantity -= 1
      if (line.quantity <= 0) {
        this.cartLines = this.cartLines.filter((l) => l.id !== id)
        delete this.selected[id]
      }
    },
    async submit(checkout) {
      this.loading = true
      this.msg = ''
      const res = await api.createOrder({
        items: this.cartLines.map((l) => ({ product_id: l.id, quantity: l.quantity })),
        table_number: this.tableNumber,
        checkout,
      })
      this.loading = false
      if (res.success) {
        this.msgType = 'success'
        this.msg = (checkout ? '已建立並完成現金結帳，訂單 #' : '訂單 #') + res.data.order_id
        this.cartLines = []
        this.selected = {}
        this.tableNumber = 0
      } else {
        this.msgType = 'error'
        this.msg = res.message
      }
    },
  },
}
</script>

<style scoped>
.pos-layout { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
.pos-left { padding: 20px; }
.pos-right { padding: 20px; position: sticky; top: 20px; }
.pos-toolbar { display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; }
.search-input {
  width: 100%; padding: 10px 14px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; outline: none;
}
.search-input:focus { border-color: #4CAF50; }
.chip-group { display: flex; flex-wrap: wrap; gap: 8px; }
.chip {
  padding: 6px 14px; border: 1px solid #e0e0e0; border-radius: 20px; background: #fff; font-size: 13px; color: #666; cursor: pointer; transition: all 0.2s;
}
.chip:hover { border-color: #4CAF50; color: #4CAF50; }
.chip.active { background: #4CAF50; border-color: #4CAF50; color: #fff; }
.pos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; }
.pos-item {
  border: 1px solid #e8e8e8; border-radius: 10px; overflow: hidden; cursor: pointer; transition: all 0.15s; background: #fff;
}
.pos-item:hover { border-color: #4CAF50; box-shadow: 0 2px 8px rgba(76, 175, 80, 0.15); }
.pos-item.selected { border-color: #4CAF50; background: #f1f8f1; box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.25); }
.pos-item-img { height: 90px; display: flex; align-items: center; justify-content: center; background: #fafafa; font-size: 34px; color: #ccc; }
.pos-item-img img { width: 100%; height: 100%; object-fit: cover; }
.pos-item-name { padding: 8px 10px 0; font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pos-item-price { padding: 0 10px 10px; font-size: 14px; color: #e53935; font-weight: 700; }
.pos-right-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.pos-total { text-align: right; }
.pos-total span { display: block; font-size: 12px; color: #999; }
.pos-total strong { font-size: 22px; color: #e53935; }
.pos-cart { max-height: 320px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px; padding: 8px 12px; margin-bottom: 16px; }
.cart-line { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 0; border-bottom: 1px dashed #eee; }
.cart-line:last-child { border-bottom: none; }
.cart-name { flex: 1; font-size: 13px; }
.cart-qty { display: flex; align-items: center; gap: 6px; }
.cart-qty button {
  width: 24px; height: 24px; border-radius: 6px; border: 1px solid #ddd; background: #fff; cursor: pointer; color: #555; font-size: 14px; line-height: 1;
}
.cart-qty button:hover { border-color: #4CAF50; color: #4CAF50; }
.cart-qty span { min-width: 22px; text-align: center; font-weight: 600; }
.cart-sub { min-width: 70px; text-align: right; font-size: 13px; font-weight: 600; color: #e53935; }
.pos-cart-empty { text-align: center; color: #bbb; padding: 48px 0; }
.pos-cart-empty i { font-size: 40px; }
.pos-cart-empty p { margin-top: 8px; font-size: 13px; }
.pos-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
</style>
