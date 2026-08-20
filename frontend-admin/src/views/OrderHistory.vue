<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-history"></i> 訂單歷程</h1>
      <router-link to="/orders" class="btn btn-default"><i class="fas fa-tachometer-alt"></i> 出單看板</router-link>
    </div>

    <div v-if="msg" :class="'msg msg-' + msgType">{{ msg }}</div>

    <div class="card">
      <div class="date-bar">
        <label><span>開始日期</span><input type="date" v-model="startDate" @change="onDateChange" /></label>
        <label><span>結束日期</span><input type="date" v-model="endDate" @change="onDateChange" /></label>
        <button class="btn btn-default" :disabled="!startDate && !endDate" @click="clearDates"><i class="fas fa-times"></i> 清除</button>
      </div>

      <div class="income-bar">
        <i class="fas fa-coins"></i>
        <div>
          <span>{{ incomeLabel }}</span>
          <strong>{{ fmt(income) }}</strong>
        </div>
      </div>

      <div class="filter-bar">
        <button
          v-for="(f, key) in filters"
          :key="key"
          class="chip"
          :class="{ active: status === key }"
          @click="selectStatus(key)"
        >
          {{ f }}
        </button>
      </div>

      <div v-if="loading" style="text-align:center;color:#888;padding:48px;"><i class="fas fa-spinner fa-spin"></i> 載入中...</div>

      <table v-else class="history-table">
        <thead>
          <tr>
            <th>訂單編號</th>
            <th>時間</th>
            <th>用餐方式</th>
            <th>付款方式</th>
            <th style="text-align:center;">金額</th>
            <th>狀態</th>
            <th style="text-align:center;">動作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!rows.length">
            <td colspan="7" style="text-align:center;color:#999;padding:40px;">沒有符合的訂單</td>
          </tr>
          <tr v-for="o in rows" :key="o.id">
            <td style="font-weight:600;">#{{ o.id }}</td>
            <td>{{ formatDate(o.created_at) }}</td>
            <td>{{ orderTypeLabel(o.order_type) }}<span v-if="o.order_type === 'dine_in' && o.table_number"> · {{ o.table_number }} 號桌</span></td>
            <td>{{ payLabel(o.payment_method) }}</td>
            <td style="text-align:center;font-weight:600;">{{ fmt(o.total_amount) }}</td>
            <td><span :class="'badge badge-' + o.status">{{ STATUS[o.status] || o.status }}</span></td>
            <td style="text-align:center;">
              <router-link :to="'/orders/' + o.id" class="btn btn-default btn-sm"><i class="fas fa-eye"></i> 查看</router-link>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="!loading && totalPages > 1" class="pagination">
        <span>共 {{ total }} 筆 · 第 {{ page }} / {{ totalPages }} 頁</span>
        <div class="btns">
          <button :disabled="page <= 1" @click="goPage(page - 1)"><i class="fas fa-chevron-left"></i> 上一頁</button>
          <button :disabled="page >= totalPages" @click="goPage(page + 1)">下一頁 <i class="fas fa-chevron-right"></i></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'

export default {
  name: 'OrderHistoryView',
  data() {
    return {
      STATUS: { pending: '待付款', paid: '已付款', shipped: '製作中', completed: '已完成', cancelled: '已取消' },
      filters: { '': '全部', pending: '待付款', paid: '已付款', shipped: '製作中', completed: '已完成', cancelled: '已取消' },
      status: '',
      startDate: '',
      endDate: '',
      rows: [],
      page: 1,
      perPage: 20,
      total: 0,
      totalPages: 1,
      income: 0,
      loading: true,
      msg: '',
      msgType: 'success',
    }
  },
  computed: {
    incomeLabel() {
      if (this.startDate || this.endDate) {
        return `期間收入（${this.startDate || '…'} ~ ${this.endDate || '…'}）`
      }
      return '全部訂單總金額'
    },
  },
  async created() {
    await this.load()
  },
  methods: {
    fmt(n) {
      return 'NT$ ' + Number(n).toLocaleString()
    },
    payLabel(m) {
      return { linepay: 'LINE Pay', cod: '貨到付款', cash: '現金' }[m] || '—'
    },
    orderTypeLabel(t) {
      return { dine_in: '內用', takeout: '外帶' }[t] || '—'
    },
    formatDate(s) {
      if (!s) return '—'
      return String(s).replace('T', ' ').slice(0, 16)
    },
    selectStatus(key) {
      if (key === this.status) return
      this.status = key
      this.page = 1
      this.load()
    },
    onDateChange() {
      this.page = 1
      this.load()
    },
    clearDates() {
      this.startDate = ''
      this.endDate = ''
      this.page = 1
      this.load()
    },
    goPage(p) {
      this.page = p
      this.load()
    },
    async load() {
      this.loading = true
      this.msg = ''
      const res = await api.orders({
        status: this.status || undefined,
        page: this.page,
        per_page: this.perPage,
        start: this.startDate || undefined,
        end: this.endDate || undefined,
      })
      this.loading = false
      if (res.success) {
        this.rows = res.data.items || []
        this.total = res.data.total || 0
        this.totalPages = res.data.total_pages || 1
        this.income = res.data.income || 0
      } else {
        this.msgType = 'error'
        this.msg = res.message || '載入失敗'
      }
    },
  },
}
</script>

<style scoped>
.filter-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
.date-bar { display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 16px; }
.date-bar label { display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: #555; font-weight: 600; }
.date-bar input[type="date"] { padding: 8px 10px; border: 1px solid #d0d5dd; border-radius: 6px; font-size: 14px; outline: none; }
.date-bar input[type="date"]:focus { border-color: #4CAF50; }
.income-bar { display: flex; align-items: center; gap: 12px; background: #f1f8f1; border: 1px solid #c8e6c9; border-radius: 10px; padding: 14px 18px; margin-bottom: 18px; }
.income-bar > i { font-size: 22px; color: #2e7d32; }
.income-bar span { display: block; font-size: 13px; color: #555; }
.income-bar strong { font-size: 20px; color: #2e7d32; }
.chip { padding: 8px 16px; border: 1px solid #e0e0e0; border-radius: 20px; background: #fff; font-size: 13px; color: #666; cursor: pointer; transition: all 0.2s; }
.chip:hover { border-color: #4CAF50; color: #4CAF50; }
.chip.active { background: #4CAF50; border-color: #4CAF50; color: #fff; }
.history-table th { white-space: nowrap; }
.btn-sm { padding: 6px 12px; font-size: 12px; }
</style>