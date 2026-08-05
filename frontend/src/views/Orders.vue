<template>
  <div>
    <h1 style="margin-bottom:24px;">我的訂單</h1>
    <div class="status-tabs">
      <button v-for="t in tabs" :key="t.value" class="status-tab" :class="{ active: status === t.value }" @click="selectStatus(t.value)">{{ t.label }}</button>
    </div>
    <div v-if="loading" style="text-align:center;padding:60px;color:#888;">載入中...</div>
    <div v-else-if="!orders.length" style="text-align:center;padding:60px;color:#888;">
      <i class="fas fa-file-invoice" style="font-size:48px;color:#ccc;margin-bottom:16px;"></i>
      <p>{{ status ? '沒有此狀態的訂單' : '尚無訂單' }}</p>
      <router-link to="/" class="btn btn-primary" style="margin-top:16px;">去購物</router-link>
    </div>
    <div v-else class="order-list">
      <div v-for="o in orders" :key="o.id" class="order-card" @click="$router.push(`/orders/${o.id}`)">
        <div class="order-header">
          <span class="order-id">訂單 #{{ o.id }}</span>
          <span class="order-status" :class="o.status">{{ statusLabel(o.status) }}</span>
        </div>
        <div class="order-body">
          <span>NT$ {{ Number(o.total_amount).toLocaleString() }}</span>
          <span class="order-date">{{ formatDate(o.created_at) }}</span>
        </div>
      </div>
    </div>
  </div>
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
    formatDate(v) {
      if (!v) return ''
      const d = new Date(v.replace(' ', 'T'))
      if (isNaN(d)) return v
      const pad = n => String(n).padStart(2, '0')
      return `${d.getFullYear()}/${pad(d.getMonth() + 1)}/${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
    },
  },
  async created() {
    await this.selectStatus('')
  },
}
</script>

<style scoped>
.status-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
.status-tab { padding: 7px 16px; border-radius: 20px; border: 1px solid #e0e0e0; background: #fff; color: #666; font-size: 13px; cursor: pointer; transition: all 0.2s; }
.status-tab:hover { border-color: #4CAF50; color: #4CAF50; }
.status-tab.active { background: #4CAF50; border-color: #4CAF50; color: #fff; }
.order-card { background: #fff; border-radius: 10px; padding: 18px 24px; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); cursor: pointer; transition: box-shadow 0.2s; }
.order-card:hover { box-shadow: 0 3px 10px rgba(0,0,0,0.08); }
.order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.order-id { font-weight: 600; font-size: 15px; }
.order-status { padding: 3px 10px; border-radius: 12px; font-size: 12px; }
.order-status.pending { background: #f5f5f5; color: #666; }
.order-status.paid { background: #e3f2fd; color: #1565c0; }
.order-status.shipped { background: #fff3e0; color: #e65100; }
.order-status.completed { background: #e8f5e9; color: #2e7d32; }
.order-status.cancelled { background: #ffebee; color: #c62828; }
.order-body { display: flex; justify-content: space-between; font-size: 14px; color: #888; }
</style>
