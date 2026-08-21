<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-shopping-cart"></i> 訂單 #{{ order?.id }}</h1>
      <div class="header-actions">
        <button v-if="order && editable" class="btn btn-primary" @click="showEdit = true">
          <i class="fas fa-edit"></i> 修改訂單
        </button>
        <router-link to="/orders" class="btn btn-default"><i class="fas fa-arrow-left"></i> 返回列表</router-link>
      </div>
    </div>

    <div v-if="loading" class="card" style="text-align:center;color:#888;padding:48px;"><i class="fas fa-spinner fa-spin"></i> 載入中...</div>
    <p v-else-if="!order" style="text-align:center;padding:48px;color:#888;">訂單不存在</p>

    <template v-else>
      <div v-if="msg" :class="'msg msg-' + msgType">{{ msg }}</div>

      <div class="card">
        <h3><i class="fas fa-truck"></i> 收件資訊</h3>
        <div class="info-row"><span>收件人</span><b>{{ order.receiver_name || '—' }}</b></div>
          <div class="info-row"><span>收件電話</span><b>{{ order.receiver_phone || '—' }}</b></div>
          <div class="info-row"><span>收件地址</span><b>{{ order.receiver_address || '—' }}</b></div>
          <div class="info-row">
            <span>用餐方式</span>
            <b>{{ orderTypeLabel(order.order_type) }}<span v-if="order.order_type === 'dine_in' && order.table_number"> · {{ order.table_number }} 號桌</span></b>
          </div>
          <div class="info-row"><span>付款方式</span>            <b>{{ payLabel(order) }}</b></div>
<div class="info-row"><span>訂單時間</span><b>{{ formatDate(order.created_at) }}</b></div>
      </div>

      <div class="card">
        <h3><i class="fas fa-box-open"></i> 訂單項目</h3>
        <table>
          <thead>
            <tr><th style="width:60px;">圖片</th><th>商品</th><th style="text-align:center;">單價</th><th style="text-align:center;">數量</th><th style="text-align:center;">小計</th></tr>
          </thead>
          <tbody>
            <tr v-for="item in order.items" :key="item.id">
              <td>
                <img v-if="item.image" :src="'/uploads/' + item.image" style="width:50px;height:50px;object-fit:cover;border-radius:6px;" />
                <div v-else style="width:50px;height:50px;background:#eee;border-radius:6px;"></div>
              </td>
              <td style="font-weight:600;">{{ item.name }}</td>
              <td style="text-align:center;">{{ fmt(item.price) }}</td>
              <td style="text-align:center;">{{ item.quantity }}</td>
              <td style="text-align:center;font-weight:600;">{{ fmt(item.price * item.quantity) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4" style="text-align:right;font-weight:700;font-size:15px;">總金額</td>
              <td style="text-align:center;font-weight:700;font-size:15px;color:#e44d26;">{{ fmt(order.total_amount) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="grid-2">
        <div class="card">
          <h3><i class="fas fa-tasks"></i> 訂單狀態</h3>
          <p style="margin:10px 0 16px;color:#888;font-size:14px;">目前狀態：<span :class="'badge badge-' + order.status" style="font-size:12px;">{{ STATUS[order.status] || order.status }}</span></p>
          <div v-if="order.status === 'completed'" class="msg msg-success">訂單已完成，狀態不可再變更。</div>
          <div v-else class="status-btns">
            <button v-for="(label, key) in STATUS" :key="key" class="status-btn" :class="{ active: order.status === key }" :disabled="savingStatus" @click="changeStatus(key)">
              {{ label }}
            </button>
          </div>
        </div>
        <div class="card">
          <h3><i class="fas fa-sticky-note"></i> 管理員備註</h3>
          <textarea v-model="remark" style="width:100%;max-width:100%;padding:10px 12px;border:1px solid #d0d5dd;border-radius:6px;font-size:14px;min-height:80px;resize:vertical;" placeholder="輸入備註"></textarea>
          <button class="btn btn-primary" style="margin-top:12px;" :disabled="savingRemark" @click="saveRemark">
            <i class="fas fa-save"></i> 儲存備註
          </button>
        </div>
      </div>
    </template>

    <div v-if="showStatusConfirm" class="modal-mask" @click.self="showStatusConfirm = false">
      <div class="modal">
        <h3><i class="fas fa-tasks"></i> 確認更改訂單狀態</h3>
        <p class="modal-text">
          將訂單 <b>#{{ order?.id }}</b> 由「{{ STATUS[order?.status] || order?.status }}」更改為「{{ STATUS[pendingStatus] }}」，確定？
        </p>
        <div class="modal-actions">
          <button class="btn btn-default" @click="showStatusConfirm = false">取消</button>
          <button class="btn btn-primary" :disabled="savingStatus" @click="confirmChangeStatus">
            {{ savingStatus ? '處理中...' : '確定' }}
          </button>
        </div>
      </div>
    </div>

    <OrderEdit v-if="showEdit" :order="order" @close="showEdit = false" @saved="onOrderSaved" />
  </div>
</template>

<script>
import { api } from '../api/index.js'
import OrderEdit from '../components/OrderEdit.vue'

export default {
  name: 'OrderDetailView',
  components: { OrderEdit },
  data() {
    return {
      STATUS: { pending: '待付款', paid: '已付款', shipped: '製作中', completed: '已完成', cancelled: '已取消' },
      order: null,
      loading: true,
      msg: '',
      msgType: 'success',
      remark: '',
      savingStatus: false,
      savingRemark: false,
      showStatusConfirm: false,
      pendingStatus: '',
      showEdit: false,
    }
  },
  computed: {
    editable() {
      return this.order && !['completed', 'cancelled'].includes(this.order.status)
    },
  },
  async created() {
    await this.load()
  },
  methods: {
    fmt(n) {
      return 'NT$ ' + Number(n).toLocaleString()
    },
    payLabel(o) {
      if (!o) return ''
      if (o.status === 'pending') return '待付款'
      if (o.status === 'cancelled') return '已取消'
      return '已收款'
    },
    orderTypeLabel(t) {
      return { dine_in: '內用', takeout: '外帶' }[t] || '—'
    },
    formatDate(s) {
      if (!s) return '—'
      return String(s).replace('T', ' ').slice(0, 16)
    },
    async load() {
      this.loading = true
      const res = await api.order(this.$route.params.id)
      if (res.success) {
        this.order = res.data
        this.remark = res.data.remark || ''
      } else {
        this.msgType = 'error'
        this.msg = res.message
      }
      this.loading = false
    },
    changeStatus(status) {
      if (status === this.order.status) return
      this.pendingStatus = status
      this.showStatusConfirm = true
    },
    async confirmChangeStatus() {
      const status = this.pendingStatus
      this.savingStatus = true
      const res = await api.updateOrderStatus(this.order.id, status)
      this.savingStatus = false
      this.showStatusConfirm = false
      if (res.success) {
        this.msgType = 'success'
        this.msg = res.message
        this.order.status = status
      } else {
        this.msgType = 'error'
        this.msg = res.message
      }
    },
    async saveRemark() {
      this.savingRemark = true
      const res = await api.updateOrderRemark(this.order.id, this.remark)
      this.savingRemark = false
      this.msgType = res.success ? 'success' : 'error'
      this.msg = res.message
      if (res.success) this.order.remark = this.remark
    },
    onOrderSaved(message) {
      this.msgType = 'success'
      this.msg = message
      this.load()
    },
  },
}
</script>

<style scoped>
.header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 1100px) { .grid-2 { grid-template-columns: 1fr; } }
.card h3 { font-size: 15px; margin-bottom: 14px; color: #333; }
.info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #eee; font-size: 14px; }
.info-row span { color: #888; }
.info-row b { font-weight: 600; }
.status-btns { display: flex; gap: 8px; flex-wrap: wrap; }
.status-btn {
  padding: 8px 14px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fff; font-size: 13px; color: #666; cursor: pointer; transition: all 0.2s;
}
.status-btn:hover { border-color: #4CAF50; color: #4CAF50; }
.status-btn.active { background: #4CAF50; border-color: #4CAF50; color: #fff; }
.status-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.modal-mask {
  position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.modal {
  background: #fff; border-radius: 12px; padding: 28px; width: 420px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
}
.modal h3 { font-size: 17px; margin-bottom: 10px; }
.modal-text { color: #555; font-size: 14px; line-height: 1.8; }
.modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
</style>
