<template>
  <section class="orders-page">
    <div class="orders-heading">
      <div>
        <span class="eyebrow">ORDER HISTORY</span>
        <h1>我的訂單</h1>
      </div>
      <span v-if="!loading" class="order-count">{{ orders.length }} 筆</span>
    </div>

    <div class="status-tabs" role="tablist" aria-label="訂單狀態">
      <button v-for="t in tabs" :key="t.value" class="status-tab" :class="{ active: status === t.value }" type="button" role="tab" :aria-selected="status === t.value" @click="selectStatus(t.value)">{{ t.label }}</button>
    </div>

    <div v-if="loading" class="state-card loading-state">
      <span class="loader"></span>
      <span>載入訂單中</span>
    </div>

    <div v-else-if="!orders.length" class="state-card empty-state">
      <span class="material-symbols-outlined">receipt_long</span>
      <h2>{{ status ? '沒有此狀態的訂單' : '尚無訂單' }}</h2>
      <p>找到喜歡的商品後，訂單會顯示在這裡。</p>
      <router-link to="/" class="shop-button">前往購物</router-link>
    </div>

    <div v-else class="order-list">
      <article v-for="o in orders" :key="o.id" class="order-card" :class="{ completed: o.status === 'completed' }" tabindex="0" @click="openOrder(o.id)" @keydown.enter="openOrder(o.id)">
        <div class="order-header">
          <div>
            <div class="order-date">{{ formatDate(o.created_at) }}</div>
            <div class="order-id">#ORD-{{ o.id }}</div>
          </div>
          <div class="order-status" :class="o.status">
            <span class="status-dot"></span>
            <span>{{ statusLabel(o.status) }}</span>
          </div>
        </div>

        <div class="product-summary">
          <div class="product-image">
            <img v-if="o.item_image" :src="imageUrl(o.item_image)" :alt="o.item_name" />
            <span v-else class="material-symbols-outlined">shopping_bag</span>
            <span v-if="o.item_types > 1" class="more-items">+{{ o.item_types - 1 }}</span>
          </div>
          <div class="product-copy">
            <h2>{{ o.item_name || '訂單商品' }}</h2>
            <p>{{ itemLabel(o) }}</p>
          </div>
          <strong class="summary-price">NT$ {{ money(o.total_amount) }}</strong>
        </div>

        <div class="order-footer">
          <div class="total-copy">
            <span>總計</span>
            <strong>NT$ {{ money(o.total_amount) }}</strong>
          </div>
          <button type="button" class="detail-button" @click.stop="openOrder(o.id)">查看明細</button>
        </div>
      </article>
    </div>
  </section>
</template>

<script>
import { api } from '../api/index.js'
export default {
  data() {
    return {
      orders: [],
      status: '',
      loading: true,
      tabs: [
        { value: '', label: '全部' },
        { value: 'pending', label: '待付款' },
        { value: 'paid', label: '已付款' },
        { value: 'shipped', label: '出貨中' },
        { value: 'completed', label: '已完成' },
        { value: 'cancelled', label: '已取消' },
      ],
    }
  },
  methods: {
    async selectStatus(value) {
      this.status = value
      this.loading = true
      const res = await api.orders(value)
      if (res.success) this.orders = res.data
      this.loading = false
    },
    statusLabel(s) {
      const map = { pending: '待付款', paid: '已付款', shipped: '出貨中', completed: '已完成', cancelled: '已取消' }
      return map[s] || s
    },
    openOrder(id) {
      this.$router.push(`/orders/${id}`)
    },
    money(value) {
      return Number(value).toLocaleString()
    },
    imageUrl(image) {
      if (!image || image.startsWith('/') || image.startsWith('http')) return image
      return `/uploads/${image}`
    },
    itemLabel(order) {
      const count = Number(order.item_count || 0)
      return count ? `共 ${count} 件商品` : '查看訂單內容'
    },
    formatDate(v) {
      if (!v) return ''
      const d = new Date(v.replace(' ', 'T'))
      if (isNaN(d)) return v
      const pad = n => String(n).padStart(2, '0')
      return `${d.getFullYear()}.${pad(d.getMonth() + 1)}.${pad(d.getDate())}`
    },
  },
  async created() {
    await this.selectStatus('')
  },
}
</script>

<style scoped>
.orders-page {
  position: relative;
  isolation: isolate;
  min-height: 65vh;
  padding-bottom: 48px;
}
.orders-page::before {
  position: fixed;
  inset: 0;
  z-index: -1;
  background: radial-gradient(ellipse at top right, var(--shop-surface-high), var(--shop-background) 58%);
  content: '';
  pointer-events: none;
  opacity: .8;
}
.orders-heading {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 22px;
}
.eyebrow {
  display: block;
  margin-bottom: 6px;
  color: var(--shop-primary);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .16em;
}
.orders-heading h1 {
  margin: 0;
  color: var(--shop-text);
  font-family: 'Sora', sans-serif;
  font-size: clamp(28px, 5vw, 42px);
  letter-spacing: -.04em;
}
.order-count {
  padding: 7px 12px;
  border: 1px solid var(--shop-border);
  border-radius: 999px;
  background: var(--shop-glass);
  color: var(--shop-text-muted);
  font-size: 12px;
}
.status-tabs {
  display: flex;
  gap: 24px;
  margin: 0 -16px 20px;
  padding: 0 16px;
  border-bottom: 1px solid var(--shop-border);
  overflow-x: auto;
  scrollbar-width: none;
}
.status-tabs::-webkit-scrollbar { display: none; }
.status-tab {
  flex: 0 0 auto;
  height: 46px;
  padding: 0 2px;
  border: 0;
  border-bottom: 2px solid transparent;
  background: transparent;
  color: var(--shop-text-muted);
  font: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: color .2s, border-color .2s;
}
.status-tab:hover,
.status-tab.active {
  border-bottom-color: var(--shop-primary);
  color: var(--shop-primary);
}
.order-list {
  display: grid;
  gap: 16px;
}
.order-card,
.state-card {
  border: 1px solid var(--shop-border);
  border-radius: 16px;
  background: var(--shop-glass-card);
  box-shadow: 0 16px 36px rgba(0, 0, 0, .18);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
}
.order-card {
  padding: 20px;
  cursor: pointer;
  transition: border-color .2s, box-shadow .2s, transform .2s;
}
.order-card:hover,
.order-card:focus-visible {
  border-color: rgba(117, 255, 158, .36);
  box-shadow: 0 20px 46px rgba(0, 0, 0, .28);
  outline: none;
  transform: translateY(-2px);
}
.order-card.completed { opacity: .82; }
.order-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
}
.order-date {
  margin-bottom: 4px;
  color: var(--shop-text-muted);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .12em;
}
.order-id {
  color: var(--shop-text);
  font-family: 'Sora', sans-serif;
  font-size: 17px;
  font-weight: 700;
}
.order-status {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 7px 11px;
  border: 1px solid var(--shop-border);
  border-radius: 999px;
  background: var(--shop-glass);
  color: var(--shop-text-muted);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .06em;
  white-space: nowrap;
}
.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: currentColor;
}
.order-status.pending,
.order-status.cancelled { color: var(--shop-error); }
.order-status.paid,
.order-status.shipped { color: var(--shop-primary); }
.order-status.pending .status-dot,
.order-status.shipped .status-dot {
  animation: status-pulse 1.8s ease-in-out infinite;
}
.product-summary {
  display: grid;
  grid-template-columns: 72px minmax(0, 1fr);
  gap: 14px;
  align-items: center;
  margin-bottom: 18px;
}
.product-image {
  position: relative;
  display: grid;
  width: 72px;
  height: 72px;
  place-items: center;
  border: 1px solid var(--shop-border);
  border-radius: 12px;
  background: var(--shop-surface-lowest);
  color: var(--shop-text-muted);
  overflow: hidden;
}
.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.product-image > .material-symbols-outlined { font-size: 30px; }
.more-items {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  background: rgba(0, 0, 0, .58);
  color: var(--shop-text);
  font-family: 'Sora', sans-serif;
  font-weight: 700;
  backdrop-filter: blur(2px);
}
.product-copy { min-width: 0; }
.product-copy h2 {
  margin: 0;
  color: var(--shop-text);
  font-size: 15px;
  line-height: 1.35;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.product-copy p {
  margin: 6px 0 0;
  color: var(--shop-text-muted);
  font-size: 13px;
}
.summary-price {
  grid-column: 2;
  color: var(--shop-primary);
  font-family: 'Sora', sans-serif;
  font-size: 15px;
}
.order-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--shop-border);
}
.total-copy {
  display: grid;
  gap: 2px;
}
.total-copy span {
  color: var(--shop-text-muted);
  font-size: 11px;
}
.total-copy strong {
  color: var(--shop-text);
  font-family: 'Sora', sans-serif;
  font-size: 16px;
}
.detail-button,
.shop-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 16px;
  border: 1px solid var(--shop-border);
  border-radius: 10px;
  background: var(--shop-surface-highest);
  color: var(--shop-text);
  font: inherit;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
  transition: border-color .2s, background .2s, color .2s;
}
.detail-button:hover,
.shop-button:hover {
  border-color: var(--shop-primary);
  background: rgba(117, 255, 158, .12);
  color: var(--shop-primary);
}
.state-card {
  display: grid;
  justify-items: center;
  gap: 10px;
  padding: 54px 20px;
  color: var(--shop-text-muted);
  text-align: center;
}
.empty-state > .material-symbols-outlined {
  margin-bottom: 4px;
  color: var(--shop-primary);
  font-size: 48px;
}
.empty-state h2 {
  margin: 0;
  color: var(--shop-text);
  font-size: 19px;
}
.empty-state p { margin: 0 0 10px; font-size: 13px; }
.loading-state { min-height: 220px; align-content: center; }
.loader {
  width: 28px;
  height: 28px;
  border: 2px solid var(--shop-border);
  border-top-color: var(--shop-primary);
  border-radius: 50%;
  animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes status-pulse {
  50% { opacity: .45; box-shadow: 0 0 12px currentColor; }
}
@media (min-width: 720px) {
  .status-tabs { margin-right: 0; margin-left: 0; padding: 0; }
  .product-summary { grid-template-columns: 80px minmax(0, 1fr) auto; }
  .product-image { width: 80px; height: 80px; }
  .summary-price { grid-column: auto; }
}
@media (min-width: 980px) {
  .order-list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .order-card { padding: 22px; }
}
@media (max-width: 380px) {
  .status-tabs { gap: 18px; }
  .order-card { padding: 16px; }
  .order-id { font-size: 15px; }
  .summary-price { font-size: 14px; }
}
</style>
