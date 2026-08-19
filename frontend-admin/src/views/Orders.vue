<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-shopping-cart"></i> 訂單列表</h1>
    </div>

    <div class="card filter-bar">
      <button
        v-for="(label, key) in STATUS"
        :key="key"
        class="status-btn"
        :class="{ active: status === key }"
        @click="setStatus(key)"
      >
        {{ label }}
      </button>
    </div>

    <div class="card" style="padding:0;overflow:hidden;">
      <div v-if="loading" style="text-align:center;padding:48px;color:#888;"><i class="fas fa-spinner fa-spin"></i> 載入中...</div>
      <p v-else-if="!items.length" style="text-align:center;padding:48px;color:#888;">尚無訂單</p>
      <template v-else>
        <table>
          <thead>
            <tr>
              <th>訂單編號</th>
              <th>用餐方式</th>
              <th style="text-align:center;">金額</th>
              <th style="text-align:center;">付款方式</th>
              <th style="text-align:center;">狀態</th>
              <th style="text-align:center;">備註</th>
              <th style="text-align:center;">日期</th>
              <th style="text-align:center;width:90px;">操作</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="o in items" :key="o.id" style="cursor:pointer;" @click="goDetail(o.id)">
              <td style="color:#4CAF50;font-weight:600;">#{{ o.id }}</td>
              <td>
                <span :class="'badge ' + (o.order_type === 'takeout' ? 'badge-takeout' : 'badge-dinein')">{{ o.order_type === 'takeout' ? '外帶' : '內用' }}</span>
                <span v-if="o.order_type === 'dine_in' && o.table_number" style="color:#888;font-size:12px;margin-left:6px;">{{ o.table_number }} 號桌</span>
              </td>
              <td style="text-align:center;font-weight:600;">{{ fmt(o.total_amount) }}</td>
              <td style="text-align:center;">{{ payLabel(o.payment_method) }}</td>
              <td style="text-align:center;"><span :class="'badge badge-' + o.status">{{ STATUS[o.status] || o.status }}</span></td>
              <td style="text-align:center;">
                <span v-if="o.remark" :title="o.remark" style="color:#888;font-size:13px;">{{ o.remark }}</span>
                <span v-else style="color:#ccc;">—</span>
              </td>
              <td style="text-align:center;color:#888;font-size:13px;">{{ formatDate(o.created_at) }}</td>
              <td style="text-align:center;" @click.stop>
                <router-link :to="`/orders/${o.id}`" style="color:#4CAF50;font-size:16px;" title="檢視"><i class="fas fa-eye"></i></router-link>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="pagination" v-if="totalPages > 1">
          <span>共 {{ total }} 筆</span>
          <div class="btns">
            <button :disabled="page <= 1" @click="goPage(page - 1)">上一頁</button>
            <span>{{ page }} / {{ totalPages }}</span>
            <button :disabled="page >= totalPages" @click="goPage(page + 1)">下一頁</button>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'

export default {
  name: 'OrdersView',
  data() {
    return {
      STATUS: { pending: '待付款', paid: '已付款', shipped: '出貨中', completed: '已完成', cancelled: '已取消' },
      items: [],
      status: '',
      page: 1,
      totalPages: 1,
      total: 0,
      loading: true,
    }
  },
  created() {
    this.load()
  },
  methods: {
    fmt(n) {
      return 'NT$ ' + Number(n).toLocaleString()
    },
    payLabel(m) {
      return { linepay: 'LINE Pay', cod: '貨到付款', cash: '現金' }[m] || '—'
    },
    formatDate(s) {
      if (!s) return '—'
      return String(s).replace('T', ' ').slice(0, 16)
    },
    goDetail(id) {
      this.$router.push('/orders/' + id)
    },
    async load() {
      this.loading = true
      const res = await api.orders({ status: this.status, page: this.page })
      if (res.success) {
        this.items = res.data.items || []
        this.total = res.data.total
        this.totalPages = res.data.total_pages
      }
      this.loading = false
    },
    setStatus(s) {
      this.status = this.status === s ? '' : s
      this.page = 1
      this.load()
    },
    goPage(p) {
      this.page = p
      this.load()
    },
  },
}
</script>

<style scoped>
.filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; padding: 16px 24px; }
.status-btn {
  padding: 8px 16px; border: 1px solid #e0e0e0; border-radius: 20px; background: #fff; font-size: 13px; color: #666; cursor: pointer; transition: all 0.2s;
}
.status-btn:hover { border-color: #4CAF50; color: #4CAF50; }
.status-btn.active { background: #4CAF50; border-color: #4CAF50; color: #fff; }
</style>
