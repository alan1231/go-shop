<template>
  <div class="kds-card" :class="['kds-card-' + order.status, { 'kds-new': isNew }]">
    <div class="kds-card-top">
      <span class="kds-card-id">#{{ order.id }}</span>
      <span class="kds-card-time">{{ timeOf(order.created_at) }}</span>
      <span class="badge" :class="order.order_type === 'takeout' ? 'badge-takeout' : 'badge-dinein'">{{ order.order_type === 'takeout' ? '外帶' : '內用' }}</span>
      <span v-if="order.order_type === 'dine_in' && order.table_number" class="kds-table">桌{{ order.table_number }}</span>
    </div>

    <div class="kds-card-items">
      <div v-for="(it, i) in order.items" :key="i" class="kds-item-line">
        <span class="kds-item-name">{{ it.name }}</span>
        <span class="kds-item-qty">×{{ it.quantity }}</span>
        <span class="kds-item-price">{{ money(it.price * it.quantity) }}</span>
      </div>
      <p v-if="!order.items.length" class="kds-no-items">（無明細）</p>
    </div>

    <p v-if="order.remark" class="kds-remark"><i class="fas fa-sticky-note"></i> {{ order.remark }}</p>

    <div class="kds-card-bottom">
      <span class="kds-pay">{{ payLabel(order) }}</span>
      <span class="kds-total">{{ money(order.total_amount) }}</span>
    </div>

      <div class="kds-card-actions">
        <button v-if="advanceLabel" type="button" class="btn btn-primary kds-btn" @click="$emit('advance', order)">{{ advanceLabel }}</button>
        <button v-if="order.status === 'pending'" type="button" class="btn btn-default kds-btn" @click="$emit('cancel', order)">取消</button>
        <button v-if="editable" type="button" class="btn kds-btn kds-btn-edit" @click="$emit('edit', order)"><i class="fas fa-edit"></i> 修改訂單</button>
        <span v-if="order.status === 'completed'" class="kds-done-time"><i class="fas fa-check"></i> 完成 {{ timeOf(order.updated_at) }}</span>
        <button type="button" class="kds-icon-btn" title="明細" @click="$emit('open', order.id)"><i class="fas fa-eye"></i></button>
      </div>
  </div>
</template>

<script>
export default {
  name: 'KdsCard',
  props: {
    order: { type: Object, required: true },
    isNew: { type: Boolean, default: false },
  },
  emits: ['advance', 'cancel', 'open', 'edit'],
  computed: {
    advanceLabel() {
      if (this.order.status === 'pending') return '收單出餐'
      if (this.order.status === 'paid' || this.order.status === 'shipped') return '出餐完成'
      return ''
    },
    editable() {
      return this.order && !['completed', 'cancelled'].includes(this.order.status)
    },
  },
  methods: {
    money(n) {
      return 'NT$ ' + Number(n).toLocaleString()
    },
    timeOf(s) {
      if (!s) return ''
      const d = new Date(String(s).replace(' ', 'T') + 'Z')
      if (isNaN(d.getTime())) return ''
      const p = (n) => String(n).padStart(2, '0')
      return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`
    },
    payLabel(o) {
      if (!o) return ''
      if (o.status === 'pending') return '待付款'
      if (o.status === 'cancelled') return '已取消'
      return '已收款'
    },
  },
}
</script>

<style scoped>
.kds-card {
  background: #fff; border-radius: 10px; padding: 12px; margin-bottom: 12px; border-left: 4px solid #bbb;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  display: flex; flex-direction: column;
  min-width: 0;
}
.kds-card-pending { border-left-color: #f9a825; }
.kds-card-paid { border-left-color: #1e88e5; }
.kds-card-shipped { border-left-color: #8e24aa; }
.kds-card-completed { border-left-color: #2e7d32; }
.kds-card-cancelled { border-left-color: #c62828; opacity: 0.7; }
.kds-new { animation: kds-flash 1.2s ease 3; }
@keyframes kds-flash {
  0%, 100% { box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
  50% { box-shadow: 0 0 0 3px rgba(249,168,37,0.6); }
}
.kds-card-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
.kds-card-id { font-weight: 700; color: #4CAF50; font-size: 14px; }
.kds-card-time { font-size: 11px; color: #999; margin-right: auto; }
.kds-table { font-size: 12px; font-weight: 700; color: #555; background: #f5f5f5; padding: 1px 8px; border-radius: 10px; }
.kds-card-items { border-top: 1px dashed #eee; padding-top: 8px; margin-bottom: 6px; }
.kds-item-line { display: flex; align-items: baseline; gap: 6px; font-size: 13px; padding: 2px 0; }
.kds-item-name { flex: 1; }
.kds-item-qty { color: #1e88e5; font-weight: 700; }
.kds-item-price { font-size: 12px; color: #666; min-width: 62px; text-align: right; }
.kds-no-items { color: #bbb; font-size: 12px; }
.kds-remark { font-size: 12px; color: #8e6d00; background: #fff8e1; border-radius: 6px; padding: 6px 8px; margin-bottom: 8px; }
.kds-card-bottom { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.kds-done-time { font-size: 12px; color: #2e7d32; background: #e8f5e9; border-radius: 6px; padding: 4px 8px; display: inline-flex; align-items: center; gap: 4px; }
.kds-pay { font-size: 11px; color: #999; }
.kds-total { font-weight: 700; color: #e53935; font-size: 15px; }
  .kds-card-actions { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: auto; }
  .kds-btn { padding: 0 14px; height: 30px; font-size: 13px; }
  .kds-btn-edit { background: #e3f2fd; border: 1px solid #1e88e5; color: #1565c0; }
  .kds-btn-edit:hover { background: #1e88e5; color: #fff; }
.kds-icon-btn { width: 30px; height: 30px; border: 1px solid #e0e0e0; border-radius: 6px; background: #fff; color: #666; cursor: pointer; margin-left: auto; }
.kds-icon-btn:hover { color: #4CAF50; border-color: #4CAF50; }
</style>