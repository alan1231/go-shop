<template>
  <div v-if="loading" style="text-align:center;padding:60px;color:#888;">載入中...</div>
  <div v-else-if="!order" style="text-align:center;padding:60px;color:#888;">訂單不存在</div>
  <div v-else>
    <div class="page-header">
      <h1>訂單 #{{ order.id }}</h1>
      <router-link to="/orders" class="btn btn-default"><i class="fas fa-arrow-left"></i> 回訂單列表</router-link>
    </div>
    <div class="order-meta">
      <div class="card">
        <strong>狀態</strong>
        <span class="order-status" :class="order.status">{{ statusLabel(order.status) }}</span>
      </div>
      <div class="card">
        <strong>總金額</strong>
        <span style="font-size:24px;font-weight:700;color:#e44d26;">NT$ {{ Number(order.total_amount).toLocaleString() }}</span>
      </div>
      <div class="card">
        <strong>建立時間</strong>
        <span>{{ order.created_at }}</span>
      </div>
    </div>
    <button v-if="order.status === 'pending'" class="btn btn-primary" style="margin-bottom:20px;" @click="pay" :disabled="paying">
      <i class="fas fa-credit-card"></i> {{ paying ? '付款中...' : '模擬付款' }}
    </button>
    <div class="card">
      <h3 style="margin-bottom:16px;">商品明細</h3>
      <table>
        <thead><tr><th>商品</th><th>單價</th><th>數量</th><th>小計</th></tr></thead>
        <tbody>
          <tr v-for="item in order.items" :key="item.id">
            <td>
              <div class="item-cell">
                <img v-if="item.image" :src="'/php/shop/uploads/' + item.image" class="thumb" />
                {{ item.name }}
              </div>
            </td>
            <td>NT$ {{ Number(item.price).toLocaleString() }}</td>
            <td>{{ item.quantity }}</td>
            <td style="font-weight:600;">NT$ {{ (item.price * item.quantity).toLocaleString() }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'
export default {
  data() { return { order: null, loading: true, paying: false } },
  methods: {
    statusLabel(s) {
      const map = { pending: '待付款', paid: '已付款', shipped: '出貨中', completed: '已完成', cancelled: '已取消' }
      return map[s] || s
    },
    async pay() {
      this.paying = true
      const res = await api.payOrder(this.order.id)
      if (res.success) {
        this.order.status = 'paid'
      } else {
        alert(res.message)
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
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.order-meta { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
.order-meta .card { display: flex; flex-direction: column; gap: 8px; }
.order-status { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 13px; width: fit-content; }
.order-status.pending { background: #f5f5f5; color: #666; }
.order-status.paid { background: #e3f2fd; color: #1565c0; }
.order-status.shipped { background: #fff3e0; color: #e65100; }
.order-status.completed { background: #e8f5e9; color: #2e7d32; }
.order-status.cancelled { background: #ffebee; color: #c62828; }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #f0f0f0; }
th { background: #f8f9fa; font-weight: 600; }
.item-cell { display: flex; align-items: center; gap: 12px; }
.thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; }
</style>
