<template>
  <section class="detail-page">
    <div v-if="loading" class="state-card">
      <span class="loader"></span>
      <span>載入訂單中</span>
    </div>

    <div v-else-if="!order" class="state-card">
      <span class="material-symbols-outlined state-icon">receipt_long</span>
      <h1>訂單不存在</h1>
      <router-link to="/orders" class="secondary-button">返回訂單列表</router-link>
    </div>

    <template v-else>
      <div class="detail-header">
        <div>
          <router-link to="/orders" class="back-link">
            <span class="material-symbols-outlined">arrow_back</span>
            返回訂單列表
          </router-link>
          <h1>訂單詳情</h1>
          <p>訂單編號：#ORD-{{ order.id }}</p>
        </div>
        <span class="status-badge" :class="order.status">
          <span class="status-dot"></span>
          {{ statusLabel(order.status) }}
        </span>
      </div>

      <section v-if="order.status !== 'cancelled'" class="panel progress-panel">
        <div class="timeline">
          <div v-for="(step, i) in steps" :key="step.key" class="timeline-step" :class="{ done: orderIndex > i, active: orderIndex === i, reached: orderIndex >= i }">
            <div class="timeline-dot">
              <span class="material-symbols-outlined">{{ orderIndex > i ? 'check' : step.icon }}</span>
            </div>
            <span>{{ step.label }}</span>
          </div>
        </div>
      </section>

      <section v-else class="panel cancelled-panel">
        <span class="material-symbols-outlined">cancel</span>
        <div>
          <h2>訂單已取消</h2>
          <p>這筆訂單已取消，不會繼續進入付款或配送流程。</p>
        </div>
      </section>

      <div class="info-grid">
        <section class="panel info-card">
          <h2><span class="material-symbols-outlined">receipt</span>訂單資訊</h2>
          <dl>
            <div>
              <dt>建立時間</dt>
              <dd>{{ formatDate(order.created_at) }}</dd>
            </div>
            <div>
              <dt>訂單狀態</dt>
              <dd>{{ statusLabel(order.status) }}</dd>
            </div>
            <div>
              <dt>備註</dt>
              <dd>{{ order.member_remark || '無備註' }}</dd>
            </div>
          </dl>
        </section>

        <section class="panel info-card">
          <h2><span class="material-symbols-outlined">pin_drop</span>配送資訊</h2>
          <dl>
            <div>
              <dt>收件人</dt>
              <dd>{{ order.receiver_name || '未提供' }}</dd>
            </div>
            <div>
              <dt>聯絡電話</dt>
              <dd>{{ order.receiver_phone || '未提供' }}</dd>
            </div>
            <div>
              <dt>配送地址</dt>
              <dd>{{ order.receiver_address || '未提供' }}</dd>
            </div>
          </dl>
        </section>
      </div>

      <section class="panel items-panel">
        <div class="section-heading">
          <h2>商品明細</h2>
          <span>{{ totalQuantity }} 件商品</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>商品</th><th>單價</th><th>數量</th><th>小計</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in order.items" :key="item.id">
                <td>
                  <div class="item-cell">
                    <div class="thumb">
                      <img v-if="item.image" :src="imageUrl(item.image)" :alt="item.name" />
                      <span v-else class="material-symbols-outlined">inventory_2</span>
                    </div>
                    <span>{{ item.name }}</span>
                  </div>
                </td>
                <td>NT$ {{ money(item.price) }}</td>
                <td>{{ item.quantity }}</td>
                <td class="subtotal">NT$ {{ money(item.price * item.quantity) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="totals">
          <div><span>商品總計</span><strong>NT$ {{ money(productTotal) }}</strong></div>
          <div class="grand-total"><span>總金額</span><strong>NT$ {{ money(order.total_amount) }}</strong></div>
        </div>
      </section>

      <div v-if="order.status === 'pending'" class="action-area">
        <button class="pay-button" type="button" :disabled="paying" @click="pay">
          <span class="material-symbols-outlined">account_balance_wallet</span>
          {{ paying ? '付款中...' : '立即付款' }}
        </button>
      </div>
    </template>
  </section>
</template>

<script>
import { api } from '../api/index.js'
import { toastStore } from '../store/toast.js'
export default {
  data() { return { order: null, loading: true, paying: false } },
  computed: {
    steps() {
      return [
        { key: 'pending', label: '待付款', icon: 'pending_actions' },
        { key: 'paid', label: '已付款', icon: 'credit_card' },
        { key: 'shipped', label: '配送中', icon: 'local_shipping' },
        { key: 'completed', label: '已完成', icon: 'task_alt' },
      ]
    },
    orderIndex() {
      if (!this.order) return -1
      return this.steps.findIndex(s => s.key === this.order.status)
    },
    totalQuantity() {
      return (this.order?.items || []).reduce((sum, item) => sum + Number(item.quantity), 0)
    },
    productTotal() {
      return (this.order?.items || []).reduce((sum, item) => sum + Number(item.price) * Number(item.quantity), 0)
    },
  },
  methods: {
    statusLabel(status) {
      const labels = { pending: '待付款', paid: '已付款', shipped: '配送中', completed: '已完成', cancelled: '已取消' }
      return labels[status] || status
    },
    money(value) {
      return Number(value || 0).toLocaleString()
    },
    imageUrl(image) {
      if (!image || image.startsWith('/') || image.startsWith('http')) return image
      return `/uploads/${image}`
    },
    formatDate(value) {
      if (!value) return '未提供'
      const date = new Date(value.replace(' ', 'T'))
      if (isNaN(date)) return value
      const pad = number => String(number).padStart(2, '0')
      return `${date.getFullYear()}/${pad(date.getMonth() + 1)}/${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`
    },
    async pay() {
      this.paying = true
      const res = await api.payOrder(this.order.id)
      if (res.success) {
        this.order.status = 'paid'
        toastStore.success('付款成功！')
      } else {
        toastStore.error(res.message)
      }
      this.paying = false
    },
  },
  async created() {
    const res = await api.order(this.$route.params.id)
    if (res.success) this.order = res.data
    this.loading = false
  },
}
</script>

<style scoped>
.detail-page {
  position: relative;
  max-width: 960px;
  min-height: 65vh;
  margin: 0 auto;
  padding-bottom: 48px;
}
.detail-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--shop-border);
}
.back-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 16px;
  color: var(--shop-text-muted);
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: color .2s;
}
.back-link:hover { color: var(--shop-primary); }
.back-link .material-symbols-outlined { font-size: 18px; }
.detail-header h1 {
  margin: 0 0 6px;
  color: var(--shop-text);
  font-family: 'Sora', sans-serif;
  font-size: clamp(28px, 5vw, 40px);
  letter-spacing: -.04em;
}
.detail-header p {
  margin: 0;
  color: var(--shop-text-muted);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: .05em;
}
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 14px;
  border: 1px solid var(--shop-border);
  border-radius: 999px;
  background: var(--shop-glass);
  color: var(--shop-text-muted);
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}
.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: currentColor;
}
.status-badge.pending,
.status-badge.cancelled { color: var(--shop-error); }
.status-badge.paid,
.status-badge.shipped { color: var(--shop-primary); }
.panel,
.state-card {
  border: 1px solid var(--shop-border);
  border-radius: 16px;
  background: var(--shop-glass-card);
  box-shadow: 0 16px 36px rgba(0, 0, 0, .18);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
}
.progress-panel { margin-bottom: 24px; padding: 26px 20px; }
.timeline { display: flex; align-items: flex-start; }
.timeline-step {
  position: relative;
  flex: 1;
  color: var(--shop-text-muted);
  font-size: 11px;
  font-weight: 700;
  text-align: center;
}
.timeline-step::before {
  position: absolute;
  top: 23px;
  right: 50%;
  width: 100%;
  height: 2px;
  background: var(--shop-surface-highest);
  content: '';
}
.timeline-step:first-child::before { display: none; }
.timeline-step.reached::before { background: var(--shop-primary-strong); }
.timeline-dot {
  position: relative;
  z-index: 1;
  display: grid;
  width: 48px;
  height: 48px;
  margin: 0 auto 12px;
  place-items: center;
  border: 4px solid var(--shop-surface-low);
  border-radius: 50%;
  background: var(--shop-surface-highest);
  color: var(--shop-text-muted);
}
.timeline-dot .material-symbols-outlined { font-size: 21px; }
.timeline-step.done,
.timeline-step.active { color: var(--shop-primary); }
.timeline-step.done .timeline-dot,
.timeline-step.active .timeline-dot {
  background: var(--shop-primary);
  color: var(--shop-on-primary);
}
.timeline-step.active .timeline-dot { box-shadow: 0 0 18px color-mix(in srgb, var(--shop-primary) 35%, transparent); }
.cancelled-panel {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  padding: 22px;
  color: var(--shop-error);
}
.cancelled-panel > .material-symbols-outlined { font-size: 36px; }
.cancelled-panel h2 { margin: 0 0 4px; color: var(--shop-error); font-size: 18px; }
.cancelled-panel p { margin: 0; color: var(--shop-text-muted); font-size: 13px; }
.info-grid {
  display: grid;
  gap: 16px;
  margin-bottom: 24px;
}
.info-card {
  padding: 22px;
  transition: border-color .2s;
}
.info-card:hover { border-color: color-mix(in srgb, var(--shop-primary) 35%, transparent); }
.info-card h2,
.section-heading h2 {
  margin: 0;
  color: var(--shop-text);
  font-family: 'Sora', sans-serif;
  font-size: 18px;
}
.info-card h2 {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 18px;
}
.info-card h2 .material-symbols-outlined { color: var(--shop-primary); }
.info-card dl { margin: 0; }
.info-card dl > div {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 11px 0;
  border-bottom: 1px solid var(--shop-border);
}
.info-card dl > div:last-child { border-bottom: 0; }
.info-card dt {
  flex: 0 0 auto;
  color: var(--shop-text-muted);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .06em;
}
.info-card dd {
  margin: 0;
  color: var(--shop-text);
  font-size: 13px;
  text-align: right;
  white-space: pre-wrap;
}
.items-panel { overflow: hidden; }
.section-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 20px 22px;
  border-bottom: 1px solid var(--shop-border);
  background: var(--shop-glass);
}
.section-heading > span {
  color: var(--shop-text-muted);
  font-size: 12px;
}
.table-wrap { overflow-x: auto; }
table {
  width: 100%;
  min-width: 620px;
  border-collapse: collapse;
}
th,
td {
  padding: 16px;
  border-bottom: 1px solid var(--shop-border);
  color: var(--shop-text);
  font-size: 13px;
  text-align: right;
}
th {
  background: color-mix(in srgb, var(--shop-surface-highest) 20%, transparent);
  color: var(--shop-text-muted);
  font-size: 11px;
  letter-spacing: .06em;
}
th:first-child,
td:first-child { text-align: left; }
th:nth-child(3),
td:nth-child(3) { text-align: center; }
.item-cell {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 250px;
  font-weight: 600;
}
.thumb {
  display: grid;
  flex: 0 0 64px;
  width: 64px;
  height: 64px;
  place-items: center;
  border: 1px solid var(--shop-border);
  border-radius: 9px;
  background: var(--shop-surface-lowest);
  color: var(--shop-text-muted);
  overflow: hidden;
}
.thumb img { width: 100%; height: 100%; object-fit: cover; }
.subtotal { color: var(--shop-primary); font-weight: 700; }
.totals {
  display: grid;
  justify-content: end;
  gap: 12px;
  padding: 22px;
  background: var(--shop-glass);
}
.totals > div {
  display: flex;
  justify-content: space-between;
  gap: 40px;
  width: min(100%, 280px);
  color: var(--shop-text-muted);
  font-size: 12px;
}
.totals strong { color: var(--shop-text); }
.grand-total {
  padding-top: 14px;
  border-top: 1px solid var(--shop-border);
  font-size: 15px !important;
}
.grand-total strong { color: var(--shop-primary); font-family: 'Sora', sans-serif; font-size: 19px; }
.action-area { display: flex; justify-content: flex-end; padding-top: 20px; }
.pay-button,
.secondary-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 10px;
  font: inherit;
  font-size: 12px;
  font-weight: 800;
  text-decoration: none;
  cursor: pointer;
}
.pay-button {
  padding: 14px 28px;
  border: 1px solid var(--shop-primary);
  background: var(--shop-primary);
  box-shadow: 0 0 22px color-mix(in srgb, var(--shop-primary) 25%, transparent);
  color: var(--shop-on-primary);
}
.pay-button:hover { background: var(--shop-primary-strong); }
.pay-button:disabled { cursor: wait; opacity: .6; }
.secondary-button {
  margin-top: 8px;
  padding: 10px 16px;
  border: 1px solid var(--shop-border);
  background: var(--shop-surface-highest);
  color: var(--shop-text);
}
.state-card {
  display: grid;
  min-height: 260px;
  padding: 40px 20px;
  place-items: center;
  align-content: center;
  gap: 12px;
  color: var(--shop-text-muted);
  text-align: center;
}
.state-card h1 { margin: 0; color: var(--shop-text); font-size: 22px; }
.state-icon { color: var(--shop-primary); font-size: 48px; }
.loader {
  width: 30px;
  height: 30px;
  border: 2px solid var(--shop-border);
  border-top-color: var(--shop-primary);
  border-radius: 50%;
  animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
@media (min-width: 760px) {
  .info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; }
}
@media (max-width: 620px) {
  .detail-header { align-items: flex-start; flex-direction: column; }
  .timeline {
    align-items: stretch;
    flex-direction: column;
    gap: 0;
    padding-left: 4px;
  }
  .timeline-step {
    display: grid;
    grid-template-columns: 48px 1fr;
    align-items: center;
    gap: 14px;
    min-height: 76px;
    text-align: left;
  }
  .timeline-step::before {
    top: -14px;
    right: auto;
    left: 23px;
    width: 2px;
    height: 28px;
  }
  .timeline-dot { margin: 0; }
  .info-card dl > div { align-items: flex-start; flex-direction: column; gap: 5px; }
  .info-card dd { text-align: left; }
  .totals { justify-content: stretch; }
  .totals > div { width: 100%; }
  .action-area,
  .pay-button { width: 100%; }
}
</style>
