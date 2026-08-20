<template>
  <div class="kds-add-mask" @click.self="close">
    <div class="kds-add-panel">
      <div class="kds-add-head">
        <h2><i class="fas fa-edit"></i> 修改訂單 #{{ order.id }}</h2>
        <button type="button" class="kds-add-close" aria-label="關閉" @click="close"><i class="fas fa-times"></i></button>
      </div>

      <div v-if="msg" :class="'msg msg-' + msgType" style="margin-bottom:16px;">{{ msg }}</div>

      <div class="kds-add-body">
        <div class="kds-left card">
          <div class="kds-toolbar">
            <input v-model="q" placeholder="搜尋商品加入訂單..." class="search-input" />
            <div class="chip-group">
              <button class="chip" :class="{ active: category === '' }" @click="category = ''">全部</button>
              <button v-for="c in categories" :key="c" class="chip" :class="{ active: category === c }" @click="category = c">{{ c }}</button>
            </div>
          </div>

          <div v-if="loading" class="kds-empty"><i class="fas fa-spinner fa-spin"></i> 載入中...</div>
          <div v-else-if="!visibleProducts.length" class="kds-empty">尚無上架商品</div>
          <div v-else class="kds-grid">
            <div
              v-for="p in visibleProducts"
              :key="p.id"
              class="kds-item"
              :class="{ selected: selected[p.id] }"
              @click="toggle(p)"
            >
              <div class="kds-item-img">
                <img v-if="p.image" :src="p.image" alt="" />
                <i v-else class="fas fa-utensils"></i>
              </div>
              <div class="kds-item-name">{{ p.name }}</div>
              <div class="kds-item-price">{{ fmt(p.price) }}</div>
              <span v-if="selected[p.id]" class="kds-item-badge">{{ lineQty(p.id) }}</span>
            </div>
          </div>
        </div>

        <div class="kds-right card">
          <div class="kds-total-wrap">
            <span>目前品項 ({{ cartLines.length }})</span>
            <div class="kds-total">
              <span>合計</span>
              <strong>{{ fmt(total) }}</strong>
            </div>
          </div>

          <div class="kds-cart" v-if="cartLines.length">
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
          <div v-else class="kds-cart-empty">
            <i class="fas fa-trash-alt"></i>
            <p>訂單將沒有品項</p>
          </div>

          <div class="kds-actions">
            <button class="btn btn-default" :disabled="submitting" @click="close">
              <i class="fas fa-times"></i> 取消
            </button>
            <button class="btn btn-primary" :disabled="!cartLines.length || submitting" @click="save">
              <i class="fas fa-save"></i> {{ submitting ? '儲存中...' : '儲存修改' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'

export default {
  name: 'OrderEdit',
  props: {
    order: { type: Object, required: true },
  },
  emits: ['close', 'saved'],
  data() {
    return {
      products: [],
      categories: [],
      category: '',
      q: '',
      selected: {},
      cartLines: [],
      loading: true,
      submitting: false,
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
    const [pRes, cRes] = await Promise.all([api.products({ per_page: 500 }), api.categories()])
    if (pRes.success) this.products = (pRes.data.items || []).filter((p) => p.status === 'active')
    if (cRes.success) this.categories = cRes.data || []
    ;(this.order.items || []).forEach((item) => {
      this.cartLines.push({ id: item.product_id, name: item.name, price: item.price, quantity: item.quantity })
      this.selected[item.product_id] = true
    })
    this.loading = false
  },
  methods: {
    fmt(n) {
      return 'NT$ ' + Number(n).toLocaleString()
    },
    close() {
      this.$emit('close')
    },
    lineQty(id) {
      const line = this.cartLines.find((l) => l.id === id)
      return line ? line.quantity : 0
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
    async save() {
      this.submitting = true
      this.msg = ''
      const res = await api.updateOrderItems(
        this.order.id,
        this.cartLines.map((l) => ({ product_id: l.id, quantity: l.quantity }))
      )
      this.submitting = false
      if (res.success) {
        this.$emit('saved', res.message)
        this.close()
      } else {
        this.msgType = 'error'
        this.msg = res.message
      }
    },
  },
}
</script>

<style scoped>
.kds-add-mask {
  position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 200;
  display: flex; align-items: center; justify-content: center; padding: 24px;
}
.kds-add-panel { background: #f0f2f5; border-radius: 14px; width: 100%; max-width: 980px; max-height: calc(100vh - 48px); display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.kds-add-head { display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; background: #fff; border-radius: 14px 14px 0 0; border-bottom: 1px solid #eee; }
.kds-add-head h2 { font-size: 18px; color: #1a1d29; }
.kds-add-head h2 i { color: #4CAF50; margin-right: 8px; }
.kds-add-close { width: 32px; height: 32px; border: none; background: #f0f2f5; border-radius: 50%; color: #666; cursor: pointer; font-size: 14px; }
.kds-add-close:hover { background: #e4e7eb; }
.kds-add-body { display: grid; grid-template-columns: 1fr 340px; gap: 16px; padding: 16px 24px 24px; overflow: auto; }
.kds-left { padding: 16px; margin-bottom: 0; }
.kds-toolbar { display: flex; flex-direction: column; gap: 12px; margin-bottom: 14px; }
.search-input { width: 100%; padding: 10px 14px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; outline: none; }
.search-input:focus { border-color: #4CAF50; }
.chip-group { display: flex; flex-wrap: wrap; gap: 8px; }
.chip { padding: 6px 14px; border: 1px solid #e0e0e0; border-radius: 20px; background: #fff; font-size: 13px; color: #666; cursor: pointer; transition: all 0.2s; }
.chip:hover { border-color: #4CAF50; color: #4CAF50; }
.chip.active { background: #4CAF50; border-color: #4CAF50; color: #fff; }
.kds-empty { text-align: center; color: #bbb; padding: 40px 0; font-size: 13px; }
.kds-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px; }
.kds-item { position: relative; border: 1px solid #e8e8e8; border-radius: 10px; overflow: hidden; cursor: pointer; transition: all 0.15s; background: #fff; }
.kds-item:hover { border-color: #4CAF50; box-shadow: 0 2px 8px rgba(76,175,80,0.15); }
.kds-item.selected { border-color: #4CAF50; background: #f1f8f1; box-shadow: 0 0 0 2px rgba(76,175,80,0.25); }
.kds-item-img { height: 84px; display: flex; align-items: center; justify-content: center; background: #fafafa; font-size: 30px; color: #ccc; }
.kds-item-img img { width: 100%; height: 100%; object-fit: cover; }
.kds-item-name { padding: 8px 10px 0; font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.kds-item-price { padding: 0 10px 10px; font-size: 13px; color: #e53935; font-weight: 700; }
.kds-item-badge { position: absolute; top: 6px; right: 6px; min-width: 22px; height: 22px; padding: 0 5px; background: #4CAF50; color: #fff; border-radius: 11px; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.kds-right { padding: 16px; margin-bottom: 0; display: flex; flex-direction: column; }
.kds-total-wrap { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; font-weight: 700; color: #333; }
.kds-total { text-align: right; }
.kds-total span { display: block; font-size: 12px; color: #999; font-weight: 400; }
.kds-total strong { font-size: 22px; color: #e53935; }
.kds-cart { flex: 1; max-height: 300px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px; padding: 8px 12px; margin-bottom: 12px; }
.cart-line { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 0; border-bottom: 1px dashed #eee; }
.cart-line:last-child { border-bottom: none; }
.cart-name { flex: 1; font-size: 13px; }
.cart-qty { display: flex; align-items: center; gap: 6px; }
.cart-qty button { width: 24px; height: 24px; border-radius: 6px; border: 1px solid #ddd; background: #fff; cursor: pointer; color: #555; font-size: 14px; line-height: 1; }
.cart-qty button:hover { border-color: #4CAF50; color: #4CAF50; }
.cart-qty span { min-width: 22px; text-align: center; font-weight: 600; }
.cart-sub { min-width: 70px; text-align: right; font-size: 13px; font-weight: 600; color: #e53935; }
.kds-cart-empty { text-align: center; color: #bbb; padding: 36px 0; border: 1px dashed #eee; border-radius: 8px; margin-bottom: 12px; }
.kds-cart-empty i { font-size: 36px; }
.kds-cart-empty p { margin-top: 6px; font-size: 13px; }
.kds-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: auto; }
</style>