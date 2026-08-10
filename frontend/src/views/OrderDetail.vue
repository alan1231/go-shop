<template>
  <div v-if="loading" style="text-align:center;padding:60px;color:#888;">載入中...</div>
  <div v-else-if="!order" style="text-align:center;padding:60px;color:#888;">訂單不存在</div>
  <div v-else>
    <div class="page-header">
      <h1>訂單 #{{ order.id }}</h1>
      <router-link to="/orders" class="btn btn-default"><i class="fas fa-arrow-left"></i> 回訂單列表</router-link>
    </div>
    <div v-if="order.status !== 'cancelled'" class="card">
      <h3 style="margin-bottom:16px;">訂單進度</h3>
      <div class="timeline">
        <div v-for="(step, i) in steps" :key="step.key" class="tl-step" :class="{ done: orderIndex > i, active: orderIndex === i }">
          <div class="tl-dot"><i v-if="orderIndex > i" class="fas fa-check"></i></div>
          <div class="tl-label">{{ step.label }}</div>
        </div>
      </div>
    </div>
    <div v-if="order.status === 'cancelled'" class="card" style="border-left:4px solid #f44336;">
      <h3 style="margin-bottom:12px;">訂單進度</h3>
      <p style="color:#c62828;font-weight:600;"><i class="fas fa-times-circle"></i> 此訂單已取消</p>
    </div>
    <div class="order-meta">
      <div class="card">
        <strong>總金額</strong>
        <span style="font-size:24px;font-weight:700;color:#e44d26;">NT$ {{ Number(order.total_amount).toLocaleString() }}</span>
      </div>
      <div class="card">
        <strong>建立時間</strong>
        <span>{{ order.created_at }}</span>
      </div>
    </div>
    <div v-if="order.receiver_name" class="card">
      <h3 style="margin-bottom:12px;">收件資訊</h3>
      <div class="receiver-grid">
        <div><span class="label">收件人</span><span>{{ order.receiver_name }}</span></div>
        <div><span class="label">手機</span><span>{{ order.receiver_phone || '未提供' }}</span></div>
        <div><span class="label">地址</span><span>{{ order.receiver_address || '未提供' }}</span></div>
      </div>
    </div>
    <div v-if="order.member_remark" class="card">
      <h3 style="margin-bottom:12px;">備註</h3>
      <p style="color:#555;line-height:1.7;white-space:pre-wrap;">{{ order.member_remark }}</p>
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
                <img v-if="item.image" :src="'/uploads/' + item.image" class="thumb" />
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
import { toastStore } from '../store/toast.js'
export default {
  data() { return { order: null, loading: true, paying: false } },
  computed: {
    steps() {
      return [
        { key: 'pending', label: '待付款' },
        { key: 'paid', label: '已付款' },
        { key: 'shipped', label: '出貨中' },
        { key: 'completed', label: '已完成' },
      ]
    },
    orderIndex() {
      if (!this.order) return -1
      const i = this.steps.findIndex(s => s.key === this.order.status)
      return i === -1 ? this.steps.length - 1 : i
    },
  },
  methods: {
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
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.order-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
.order-meta .card { display: flex; flex-direction: column; gap: 8px; }
.receiver-grid { display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 16px; font-size: 14px; }
.receiver-grid .label { display: block; color: #888; font-size: 12px; margin-bottom: 4px; }
.timeline { display: flex; align-items: flex-start; }
.tl-step { flex: 1; text-align: center; position: relative; }
.tl-step::before { content: ''; position: absolute; top: 14px; left: -50%; width: 100%; height: 3px; background: #e0e0e0; z-index: 0; }
.tl-step:first-child::before { display: none; }
.tl-step.done::before { background: #4CAF50; }
.tl-dot { width: 28px; height: 28px; border-radius: 50%; background: #e0e0e0; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; position: relative; z-index: 1; }
.tl-step.done .tl-dot { background: #4CAF50; }
.tl-step.active .tl-dot { background: #2196F3; box-shadow: 0 0 0 4px rgba(33,150,243,0.2); }
.tl-label { margin-top: 8px; font-size: 13px; color: #999; }
.tl-step.done .tl-label, .tl-step.active .tl-label { color: #333; font-weight: 600; }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #f0f0f0; }
th { background: #f8f9fa; font-weight: 600; }
.item-cell { display: flex; align-items: center; gap: 12px; }
.thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; }

@media (max-width: 768px) {
  .order-meta { grid-template-columns: 1fr; gap: 12px; }
  .receiver-grid { grid-template-columns: 1fr; gap: 8px; }
  .page-header { flex-direction: column; align-items: flex-start; gap: 12px; }
  .tl-label { font-size: 12px; }
}
</style>
