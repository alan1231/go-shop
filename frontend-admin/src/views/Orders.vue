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
        <KdsCard v-for="o in pendingOrders" :key="o.id" :order="o" :is-new="isNew(o)" @advance="advance" @cancel="cancel" @open="goDetail" />
      </div>

      <div class="kds-col kds-col-cooking">
        <div class="kds-col-head">
          <span>製作中</span>
          <span class="kds-count">{{ cookingOrders.length }}</span>
        </div>
        <div v-if="!cookingOrders.length" class="kds-empty">沒有訂單</div>
        <KdsCard v-for="o in cookingOrders" :key="o.id" :order="o" :is-new="isNew(o)" @advance="advance" @cancel="cancel" @open="goDetail" />
      </div>

      <div class="kds-col kds-col-completed">
        <div class="kds-col-head">
          <span>已完成</span>
          <span class="kds-count">{{ completedOrders.length }}</span>
        </div>
        <div v-if="!completedOrders.length" class="kds-empty">沒有訂單</div>
        <KdsCard v-for="o in completedOrders" :key="o.id" :order="o" :is-new="isNew(o)" @advance="advance" @cancel="cancel" @open="goDetail" />
      </div>

      <div v-if="showCancelled" class="kds-col kds-col-cancelled">
        <div class="kds-col-head">
          <span>已取消</span>
          <span class="kds-count">{{ cancelledOrders.length }}</span>
        </div>
        <div v-if="!cancelledOrders.length" class="kds-empty">沒有訂單</div>
        <KdsCard v-for="o in cancelledOrders" :key="o.id" :order="o" :is-new="isNew(o)" @advance="advance" @cancel="cancel" @open="goDetail" />
      </div>
    </div>

    <KdsAddOrder v-if="showAdd" @close="showAdd = false" @created="onCreated" />
  </div>
</template>

<script>
import { api } from '../api/index.js'
import KdsCard from '../components/KdsCard.vue'
import KdsAddOrder from '../components/KdsAddOrder.vue'

export default {
  name: 'KdsOrdersView',
  components: { KdsCard, KdsAddOrder },
  data() {
    return {
      orders: [],
      loading: true,
      showAdd: false,
      showCancelled: false,
      lastUpdate: '',
      timer: null,
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
      return this.orders.filter((o) => o.status === 'paid' || o.status === 'shipped')
    },
    completedOrders() {
      return this.orders.filter((o) => o.status === 'completed')
    },
    cancelledOrders() {
      return this.orders.filter((o) => o.status === 'cancelled')
    },
  },
  created() {
    this.load()
    this.timer = setInterval(this.load, 10000)
  },
  beforeUnmount() {
    if (this.timer) clearInterval(this.timer)
  },
  methods: {
    fmt(n) {
      return 'NT$ ' + Number(n).toLocaleString()
    },
    countBy(status) {
      return this.orders.filter((o) => o.status === status).length
    },
    isNew(o) {
      const t = new Date(String(o.created_at).replace(' ', 'T')).getTime()
      return Date.now() - t < 90 * 1000
    },
    timeOf(s) {
      if (!s) return ''
      return String(s).slice(11, 16)
    },
    async load() {
      this.loading = true
      const res = await api.orders({ per_page: 300, with_items: true })
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
.kds-board { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; align-items: start; }
.kds-board-4 { grid-template-columns: repeat(4, 1fr); }
.kds-col { background: #eef0f3; border-radius: 10px; padding: 12px; min-height: 220px; }
.kds-col-head { display: flex; justify-content: space-between; align-items: center; font-weight: 700; font-size: 14px; padding: 0 4px 10px; color: #333; }
.kds-count { background: #fff; min-width: 24px; height: 24px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; padding: 0 8px; }
.kds-empty { text-align: center; color: #aaa; font-size: 13px; padding: 30px 0; }
</style>