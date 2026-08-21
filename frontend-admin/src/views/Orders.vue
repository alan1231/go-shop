<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-utensils"></i> 出單看板</h1>
      <div class="kds-header-actions">
        <span v-if="lastUpdate" class="kds-last-update"><i class="fas fa-clock"></i> 更新於 {{ lastUpdate }}</span>
        <button class="btn btn-default" :disabled="loading" @click="load">
          <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-sync-alt'"></i> 刷新
        </button>
        <button class="btn btn-default" :class="{ 'kds-toggle-active': showCancelled }" @click="showCancelled = !showCancelled">
          <i class="fas fa-ban"></i> 已取消 ({{ countBy('cancelled') }})
        </button>
        <button class="btn btn-default" :class="{ 'kds-toggle-active': todayOnly }" @click="todayOnly = !todayOnly">
          <i class="fas fa-calendar-day"></i> 僅今日
        </button>
        <button class="btn btn-primary" @click="showAdd = true">
          <i class="fas fa-plus-circle"></i> 新增訂單
        </button>
      </div>
    </div>

    <div v-if="msg" :class="'msg msg-' + msgType">{{ msg }}</div>

    <div v-if="loading && !orders.length" style="text-align:center;padding:60px;color:#888;">
      <i class="fas fa-spinner fa-spin"></i> 載入中...
    </div>

    <div v-else class="kds-board" :class="{ 'kds-board-4': showCancelled }">
      <div class="kds-col kds-col-pending">
        <div class="kds-col-head">
          <span>待付款</span>
          <span class="kds-count">{{ pendingOrders.length }}</span>
        </div>
        <div v-if="!pendingOrders.length" class="kds-empty">沒有訂單</div>
        <KdsCard v-for="o in pendingOrders" :key="o.id" :order="o" :is-new="isNew(o)" @advance="advance" @cancel="cancel" @open="goDetail" @edit="onEdit" />
      </div>

      <div class="kds-col kds-col-cooking">
        <div class="kds-col-head">
          <span>製作中</span>
          <span class="kds-count">{{ cookingOrders.length }}</span>
        </div>
        <div v-if="!cookingOrders.length" class="kds-empty">沒有訂單</div>
        <KdsCard v-for="o in cookingOrders" :key="o.id" :order="o" :is-new="isNew(o)" @advance="advance" @cancel="cancel" @open="goDetail" @edit="onEdit" />
      </div>

      <div class="kds-col kds-col-completed">
        <div class="kds-col-head">
          <span>已完成</span>
          <span class="kds-count">{{ completedOrders.length }}</span>
        </div>
        <div v-if="!completedOrders.length" class="kds-empty">沒有訂單</div>
        <KdsCard v-for="o in completedOrders" :key="o.id" :order="o" :is-new="isNew(o)" @advance="advance" @cancel="cancel" @open="goDetail" @edit="onEdit" />
      </div>

      <div v-if="showCancelled" class="kds-col kds-col-cancelled">
        <div class="kds-col-head">
          <span>已取消</span>
          <span class="kds-count">{{ cancelledOrders.length }}</span>
        </div>
        <div v-if="!cancelledOrders.length" class="kds-empty">沒有訂單</div>
        <KdsCard v-for="o in cancelledOrders" :key="o.id" :order="o" :is-new="isNew(o)" @advance="advance" @cancel="cancel" @open="goDetail" @edit="onEdit" />
      </div>
    </div>

    <KdsAddOrder v-if="showAdd" @close="showAdd = false" @created="onCreated" />
    <OrderEdit v-if="showEdit" :order="editOrder" @close="showEdit = false" @saved="onOrderSaved" />
  </div>
</template>

<script>
import { api } from '../api/index.js'
import KdsCard from '../components/KdsCard.vue'
import KdsAddOrder from '../components/KdsAddOrder.vue'
import OrderEdit from '../components/OrderEdit.vue'

export default {
  name: 'KdsOrdersView',
  components: { KdsCard, KdsAddOrder, OrderEdit },
  data() {
    return {
      orders: [],
      loading: true,
      showAdd: false,
      showCancelled: false,
      showEdit: false,
      editOrder: null,
      todayOnly: true,
      lastUpdate: '',
      es: null,
      pollTimer: null,
      knownIds: [],
      msg: '',
      msgType: 'success',
    }
  },
  computed: {
    pendingOrders() {
      return this.orders.filter((o) => o.status === 'pending')
    },
    cookingOrders() {
      return this.orders
        .filter((o) => o.status === 'paid' || o.status === 'shipped')
        .sort((a, b) => this.ts(a) - this.ts(b))
    },
    completedOrders() {
      return this.orders
        .filter((o) => o.status === 'completed')
        .sort((a, b) => new Date(String(b.updated_at).replace(' ', 'T') + 'Z').getTime() - new Date(String(a.updated_at).replace(' ', 'T') + 'Z').getTime())
    },
    cancelledOrders() {
      return this.orders.filter((o) => o.status === 'cancelled')
    },
  },
  created() {
    this.connectStream()
    this.load()
  },
  beforeUnmount() {
    if (this.es) this.es.close()
    this.clearFallbackPoll()
  },
  watch: {
    todayOnly() {
      this.connectStream()
    },
  },
  methods: {
    fmt(n) {
      return 'NT$ ' + Number(n).toLocaleString()
    },
    todayStr() {
      const d = new Date()
      const y = d.getFullYear()
      const m = String(d.getMonth() + 1).padStart(2, '0')
      const day = String(d.getDate()).padStart(2, '0')
      return `${y}-${m}-${day}`
    },
    countBy(status) {
      return this.orders.filter((o) => o.status === status).length
    },
    isNew(o) {
      const t = new Date(String(o.created_at).replace(' ', 'T')).getTime()
      return Date.now() - t < 90 * 1000
    },
    ts(o) {
      return new Date(String(o.paid_at || o.created_at).replace(' ', 'T') + 'Z').getTime()
    },
    timeOf(s) {
      if (!s) return ''
      const d = new Date(String(s).replace(' ', 'T') + 'Z')
      if (isNaN(d.getTime())) return ''
      const p = (n) => String(n).padStart(2, '0')
      return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`
    },
    connectStream() {
      if (this.es) {
        this.es.close()
        this.es = null
      }
      const params = new URLSearchParams()
      if (this.todayOnly) {
        const t = this.todayStr()
        params.set('start', t)
        params.set('end', t)
      }
      const token = localStorage.getItem('admin_token')
      if (token) params.set('token', token)
      this.loading = true
      this.es = new EventSource('/api/admin/orders/stream?' + params.toString())
      this.es.addEventListener('orders', (e) => {
        try {
          this.clearFallbackPoll()
          this.applyOrders(JSON.parse(e.data))
        } catch (_) {}
      })
      this.es.onerror = () => {
        this.startFallbackPoll()
      }
    },
    startFallbackPoll() {
      if (this.pollTimer) return
      this.pollTimer = setInterval(() => this.load(), 5000)
    },
    clearFallbackPoll() {
      if (this.pollTimer) {
        clearInterval(this.pollTimer)
        this.pollTimer = null
      }
    },
    applyOrders(data) {
      this.loading = false
      const items = (data && data.items) || []
      const ids = items.map((o) => o.id)
      const fresh = items.filter((o) => o.status === 'pending' && !this.knownIds.includes(o.id))
      this.orders = items
      const had = this.knownIds.length > 0
      this.knownIds = ids
      if (fresh.length && had) {
        this.toast(`新訂單 #${fresh.map((o) => o.id).join('、')} 進入待付款`, 'success')
      }
      this.lastUpdate = new Date().toLocaleTimeString()
    },
    async load() {
      this.loading = true
      const params = { per_page: 300, with_items: true }
      if (this.todayOnly) {
        const t = this.todayStr()
        params.start = t
        params.end = t
      }
      const res = await api.orders(params)
      this.loading = false
      if (res.success) {
        const ids = (res.data.items || []).map((o) => o.id)
        const fresh = res.data.items.filter((o) => o.status === 'pending' && !this.knownIds.includes(o.id))
        this.orders = res.data.items || []
        this.knownIds = ids
        if (fresh.length) this.toast(`新訂單 #${fresh.map((o) => o.id).join('、')} 進入待付款`, 'success')
        this.lastUpdate = new Date().toLocaleTimeString()
      } else {
        this.toast(res.message || '載入訂單失敗', 'error')
      }
    },
    async advance(o) {
      const next = this.nextStatus(o)
      if (!next) return
      const res = await api.updateOrderStatus(o.id, next)
      if (res.success) {
        this.toast(`訂單 #${o.id} → ${this.statusLabel(next)}`, 'success')
        await this.load()
      } else {
        this.toast(res.message || '更新失敗', 'error')
      }
    },
    async cancel(o) {
      if (!window.confirm(`確定要取消訂單 #${o.id} 嗎？`)) return
      const res = await api.updateOrderStatus(o.id, 'cancelled')
      if (res.success) {
        this.toast(`訂單 #${o.id} 已取消`, 'success')
        await this.load()
      } else {
        this.toast(res.message || '取消失敗', 'error')
      }
    },
    nextStatus(o) {
      if (o.status === 'pending') return 'paid'
      if (o.status === 'paid' || o.status === 'shipped') return 'completed'
      return null
    },
    statusLabel(s) {
      return { pending: '待付款', paid: '已付款', shipped: '製作中', completed: '已完成', cancelled: '已取消' }[s] || s
    },
    goDetail(id) {
      this.$router.push('/orders/' + id)
    },
    onCreated({ orderId, checkout }) {
      this.toast(`訂單 #${orderId} 已建立${checkout ? '並現金結帳' : ''}`, 'success')
      this.load()
    },
    onEdit(order) {
      this.editOrder = order
      this.showEdit = true
    },
    onOrderSaved(message) {
      this.showEdit = false
      this.toast(message || '訂單已更新', 'success')
      this.load()
    },
    toast(msg, type) {
      this.msg = msg
      this.msgType = type
      clearTimeout(this.msgTimer)
      this.msgTimer = setTimeout(() => (this.msg = ''), 4000)
    },
  },
}
</script>

<style scoped>
.kds-header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.kds-last-update { font-size: 12px; color: #888; }
.kds-toggle-active { border: 1px solid #f44336; color: #f44336; }
.kds-board { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; align-items: start; }
.kds-board-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.kds-col { background: #eef0f3; border-radius: 10px; padding: 12px; min-height: 220px; min-width: 0; }
@media (max-width: 1200px) {
  .kds-board, .kds-board-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 760px) {
  .kds-board, .kds-board-4 { grid-template-columns: minmax(0, 1fr); }
}
.kds-col-head { display: flex; justify-content: space-between; align-items: center; font-weight: 700; font-size: 14px; padding: 0 4px 10px; color: #333; }
.kds-count { background: #fff; min-width: 24px; height: 24px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; padding: 0 8px; }
.kds-empty { text-align: center; color: #aaa; font-size: 13px; padding: 30px 0; }
</style>