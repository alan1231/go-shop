<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-tachometer-alt"></i> 儀表板</h1>
    </div>

    <div v-if="loading" class="card" style="text-align:center;color:#888;padding:48px;">
      <i class="fas fa-spinner fa-spin" style="font-size:24px;"></i> 載入中...
    </div>

    <template v-else>
      <div class="stat-grid">
        <router-link to="/orders" class="stat-card">
          <div class="stat-icon" style="color:#2196F3;"><i class="fas fa-shopping-cart"></i></div>
          <div>
            <div class="stat-num">{{ stats.totalOrders || 0 }}</div>
            <div class="stat-label">訂單總數</div>
          </div>
        </router-link>
        <router-link to="/products" class="stat-card">
          <div class="stat-icon" style="color:#4CAF50;"><i class="fas fa-box"></i></div>
          <div>
            <div class="stat-num">{{ stats.totalProducts || 0 }}</div>
            <div class="stat-label">商品總數</div>
          </div>
        </router-link>
        <router-link to="/users" class="stat-card">
          <div class="stat-icon" style="color:#9C27B0;"><i class="fas fa-users"></i></div>
          <div>
            <div class="stat-num">{{ stats.totalUsers || 0 }}</div>
            <div class="stat-label">會員總數</div>
          </div>
        </router-link>
        <div class="stat-card">
          <div class="stat-icon" style="color:#e44d26;"><i class="fas fa-dollar-sign"></i></div>
          <div>
            <div class="stat-num">NT$ {{ Number(stats.revenue || 0).toLocaleString() }}</div>
            <div class="stat-label">已完成訂單營收</div>
          </div>
        </div>
      </div>

      <div class="chart-grid">
        <div class="card">
          <h3><i class="fas fa-chart-pie"></i> 訂單狀態分布</h3>
          <div class="chart-wrap"><canvas ref="statusChart"></canvas></div>
        </div>
        <div class="card">
          <h3><i class="fas fa-chart-line"></i> 近 7 天訂單趨勢</h3>
          <div class="chart-wrap"><canvas ref="trendChart"></canvas></div>
        </div>
        <div class="card">
          <h3><i class="fas fa-fire"></i> 熱銷商品 Top 5</h3>
          <div class="chart-wrap"><canvas ref="topChart"></canvas></div>
        </div>
        <div class="card">
          <h3><i class="fas fa-coins"></i> 每日營收（NT$）</h3>
          <div class="chart-wrap"><canvas ref="revenueChart"></canvas></div>
        </div>
      </div>

      <div class="card" style="padding:0;overflow:hidden;">
        <div style="padding:20px 24px;border-bottom:1px solid #eee;">
          <h3 style="font-size:16px;"><i class="fas fa-clock"></i> 最近訂單</h3>
        </div>
        <p v-if="!recentOrders.length" style="text-align:center;padding:40px;color:#888;">尚無訂單</p>
        <table v-else>
          <thead>
            <tr><th>訂單編號</th><th style="text-align:center;">金額</th><th style="text-align:center;">狀態</th><th style="text-align:center;">日期</th></tr>
          </thead>
          <tbody>
            <tr v-for="o in recentOrders" :key="o.id" style="cursor:pointer;" @click="goOrder(o.id)">
              <td style="color:#4CAF50;font-weight:600;">#{{ o.id }}</td>
              <td style="text-align:center;">NT$ {{ Number(o.total_amount).toLocaleString() }}</td>
              <td style="text-align:center;"><span :class="'badge badge-' + o.status">{{ statusLabel(o.status) }}</span></td>
              <td style="text-align:center;color:#888;font-size:13px;">{{ formatDate(o.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>

<script>
import Chart from 'chart.js/auto'
import { api } from '../api/index.js'

const STATUS = { pending: '待付款', paid: '已付款', shipped: '出貨中', completed: '已完成', cancelled: '已取消' }
const STATUS_COLORS = { pending: '#9e9e9e', paid: '#2196F3', shipped: '#FF9800', completed: '#4CAF50', cancelled: '#f44336' }

export default {
  name: 'DashboardView',
  data() {
    return { stats: null, recentOrders: [], loading: true, charts: [] }
  },
  async created() {
    const res = await api.stats()
    if (res.success) {
      this.stats = res.data
      this.recentOrders = res.data.recentOrders || []
      this.loading = false
      await this.$nextTick()
      this.renderCharts()
      return
    }
    this.loading = false
  },
  beforeUnmount() {
    this.charts.forEach((c) => c.destroy())
  },
  methods: {
    statusLabel(s) {
      return STATUS[s] || s
    },
    formatDate(s) {
      if (!s) return '—'
      return String(s).replace('T', ' ').slice(0, 16)
    },
    goOrder(id) {
      this.$router.push('/orders/' + id)
    },
    renderCharts() {
      const daily = this.stats.dailyStats || []
      const days = daily.map((d) => d.day)
      this.charts.push(
        new Chart(this.$refs.statusChart, {
          type: 'doughnut',
          data: {
            labels: Object.keys(STATUS).map((k) => STATUS[k]),
            datasets: [
              {
                data: Object.keys(STATUS).map((k) => this.stats.statusCounts?.[k] || 0),
                backgroundColor: Object.keys(STATUS).map((k) => STATUS_COLORS[k]),
                borderWidth: 2,
                borderColor: '#fff',
              },
            ],
          },
          options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } },
        }),
        new Chart(this.$refs.trendChart, {
          type: 'line',
          data: {
            labels: days,
            datasets: [
              {
                label: '訂單數',
                data: daily.map((d) => d.orders),
                borderColor: '#2196F3',
                backgroundColor: 'rgba(33,150,243,0.12)',
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#2196F3',
              },
            ],
          },
          options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        }),
        new Chart(this.$refs.topChart, {
          type: 'bar',
          data: {
            labels: (this.stats.topProducts || []).map((p) => p.name),
            datasets: [
              {
                label: '銷售數量',
                data: (this.stats.topProducts || []).map((p) => p.sold),
                backgroundColor: ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#e44d26'],
                borderRadius: 4,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
          },
        }),
        new Chart(this.$refs.revenueChart, {
          type: 'bar',
          data: {
            labels: days,
            datasets: [
              {
                label: '營收',
                data: daily.map((d) => d.revenue),
                backgroundColor: '#4CAF50',
                borderRadius: 4,
              },
            ],
          },
          options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } },
        }),
      )
    },
  },
}
</script>

<style scoped>
.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}
.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  padding: 20px 24px;
  text-decoration: none;
  color: #1a1d29;
  transition: transform 0.15s, box-shadow 0.15s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1); }
.stat-icon { font-size: 32px; width: 48px; text-align: center; }
.stat-num { font-size: 22px; font-weight: 700; }
.stat-label { font-size: 13px; color: #888; margin-top: 2px; }
.chart-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
.chart-grid .card h3 { font-size: 15px; margin-bottom: 16px; color: #333; }
.chart-wrap { height: 260px; position: relative; }
@media (max-width: 1100px) { .chart-grid { grid-template-columns: 1fr; } }
</style>
