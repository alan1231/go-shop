<template>
  <section class="detail-page">
    <div v-if="loading" class="state-card glass-card">
      <span class="loader loader-lg"></span>
      <span>載入訂單中</span>
    </div>

    <div v-else-if="!order" class="state-card glass-card">
      <span class="material-symbols-outlined state-icon">receipt_long</span>
      <h1>訂單不存在</h1>
      <router-link to="/" class="secondary-button">回首頁</router-link>
    </div>

    <template v-else>
      <div class="detail-header">
        <div>
          <router-link to="/" class="back-link">
            <span class="material-symbols-outlined">arrow_back</span>
            回首頁
          </router-link>
          <h1>訂單詳情</h1>
          <p>訂單編號：#ORD-{{ order.id }}</p>
        </div>
        <span class="status-badge" :class="order.status">
          <span class="status-dot"></span>
          {{ statusLabel(order.status) }}
        </span>
      </div>

      <section v-if="order.status !== 'cancelled'" class="panel progress-panel glass-card">
        <div class="timeline">
          <div v-for="(step, i) in steps" :key="step.key" class="timeline-step" :class="{ done: orderIndex > i, active: orderIndex === i, reached: orderIndex >= i }">
            <div class="timeline-dot">
              <span class="material-symbols-outlined">{{ orderIndex > i ? 'check' : step.icon }}</span>
            </div>
            <span>{{ step.label }}</span>
          </div>
        </div>
      </section>

      <section v-else class="panel cancelled-panel glass-card">
        <span class="material-symbols-outlined">cancel</span>
        <div>
          <h2>訂單已取消</h2>
          <p>這筆訂單已取消，不會繼續進入付款或配送流程。</p>
        </div>
      </section>

      <div class="info-grid">
        <section class="panel info-card glass-card">
          <h2><span class="material-symbols-outlined">receipt</span>訂單資訊</h2>
          <dl>
            <div>
              <dt>建立時間</dt>
              <dd>{{ formatDate(order.created_at) }}</dd>
            </div>
            <div>
              <dt>訂單狀態</dt>
              <dd>{{ statusLabel(order.status) }}</dd>
            </div>
            <div>
              <dt>付款方式</dt>
              <dd>{{ paymentMethodLabel(order.payment_method) }}</dd>
            </div>
            <div>
              <dt>用餐方式</dt>
              <dd>{{ orderTypeLabel(order.order_type) }}<template v-if="order.order_type === 'dine_in' && order.table_number"> · {{ order.table_number }} 號桌</template></dd>
            </div>
            <div>
              <dt>備註</dt>
              <dd>{{ order.member_remark || '無備註' }}</dd>
            </div>
          </dl>
        </section>

        <section class="panel info-card glass-card">
          <h2><span class="material-symbols-outlined">pin_drop</span>配送資訊</h2>
          <dl>
            <div>
              <dt>收件人</dt>
              <dd>{{ order.receiver_name || '未提供' }}</dd>
            </div>
            <div>
              <dt>聯絡電話</dt>
              <dd>{{ order.receiver_phone || '未提供' }}</dd>
            </div>
            <div>
              <dt>配送地址</dt>
              <dd>{{ order.receiver_address || '未提供' }}</dd>
            </div>
          </dl>
        </section>
      </div>

      <section class="panel items-panel glass-card">
        <div class="section-heading">
          <h2>商品明細</h2>
          <span>{{ totalQuantity }} 件商品</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>商品</th><th>單價</th><th>數量</th><th>小計</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in order.items" :key="item.id">
                <td>
                  <div class="item-cell">
                    <div class="thumb">
                      <img v-if="item.image" :src="imageUrl(item.image)" :alt="item.name" />
                      <span v-else class="material-symbols-outlined">inventory_2</span>
                    </div>
                    <span>{{ item.name }}</span>
                  </div>
                </td>
                <td>NT$ {{ money(item.price) }}</td>
                <td>{{ item.quantity }}</td>
                <td class="subtotal">NT$ {{ money(item.price * item.quantity) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="totals">
          <div><span>商品總計</span><strong>NT$ {{ money(productTotal) }}</strong></div>
          <div class="grand-total"><span>總金額</span><strong>NT$ {{ money(order.total_amount) }}</strong></div>
        </div>
      </section>

      <div v-if="order.status === 'pending' && !payment" class="action-area">
        <span class="action-title">選擇付款方式</span>
        <button class="pay-method-btn" type="button" :disabled="paying" @click="pay('linepay')">
          <span class="material-symbols-outlined">label_important</span>
          <span class="pm-copy">
            <span class="pm-name">LINE Pay</span>
            <span class="pm-desc">QR Code 掃碼付款</span>
          </span>
        </button>
      </div>

      <section v-if="payment" class="panel payment-panel glass-card">
        <h2><span class="material-symbols-outlined">qr_code_2</span>LINE Pay 付款</h2>
        <div class="payment-body">
          <div class="qr-wrap">
            <img v-if="qrDataUrl" :src="qrDataUrl" alt="LINE Pay QR Code" />
            <span v-else class="loader loader-lg"></span>
          </div>
          <div class="payment-info">
            <p v-if="payment.sandbox" class="sandbox-hint">
              Sandbox 環境不支援 LINE App 掃描 QR Code，請點下方按鈕開啟網頁付款。
            </p>
            <p class="payment-tip">請使用 <strong>LINE App</strong> 掃描左側 QR Code，或點下方按鈕前往付款。</p>
            <button class="pay-button" type="button" @click="openPayment">
              <span class="material-symbols-outlined">open_in_new</span>
              {{ payment.sandbox ? '開啟 LINE Pay 付款' : '前往 LINE Pay 付款' }}
            </button>
            <p class="waiting-hint">
              <span class="loader loader-sm"></span>
              等待付款完成...
            </p>
            <button class="secondary-button cancel-pay" type="button" @click="cancelPay">取消付款</button>
          </div>
        </div>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useOrderStore } from '../store/order.js'
import { useToastStore } from '../store/toast.js'
import { formatDate as formatDateValue, imageUrl, money, orderStatusLabel, paymentMethodLabel } from '../utils/format.js'
import QRCode from 'qrcode'

const route = useRoute()
const orderStore = useOrderStore()
const toastStore = useToastStore()

const order = computed(() => orderStore.detail)
const loading = computed(() => orderStore.detailLoading)
const paying = computed(() => orderStore.paying)
const payment = computed(() => orderStore.payment)
const qrDataUrl = ref('')
const payWin = ref(null)

const steps = [
  { key: 'pending', label: '待付款', icon: 'pending_actions' },
  { key: 'paid', label: '已付款', icon: 'credit_card' },
  { key: 'shipped', label: '配送中', icon: 'local_shipping' },
  { key: 'completed', label: '已完成', icon: 'task_alt' },
]

const orderIndex = computed(() => {
  if (!order.value) return -1
  return steps.findIndex(s => s.key === order.value.status)
})

const totalQuantity = computed(() =>
  (order.value?.items || []).reduce((sum, item) => sum + Number(item.quantity), 0)
)

const productTotal = computed(() =>
  (order.value?.items || []).reduce((sum, item) => sum + Number(item.price) * Number(item.quantity), 0)
)

function statusLabel(status) {
  return orderStatusLabel(status, '配送中')
}

function orderTypeLabel(type) {
  return { dine_in: '內用', takeout: '外帶' }[type] || '—'
}

function formatDate(value) {
  return formatDateValue(value, { separator: '/', time: true, empty: '未提供' })
}

async function pay(method) {
  if (!order.value) return
  const res = await orderStore.pay(order.value.id, method)
  if (!res.success) {
    toastStore.error(res.message)
    return
  }
  if (res.data?.payment_access_token) {
    await generateQr()
    orderStore.startPolling(order.value.id, (poll) => {
      if (poll.data?.status === 'paid') {
        toastStore.success('付款成功！')
      } else if (poll.data?.payment === 'cancelled') {
        toastStore.error('付款已取消，請重新操作')
      }
    })
  } else {
    orderStore.payment = null
    await orderStore.loadDetail(order.value.id)
    toastStore.success('付款成功！')
  }
}

async function generateQr() {
  const token = payment.value?.payment_access_token
  if (!token) return
  qrDataUrl.value = await QRCode.toDataURL(token, { width: 220, margin: 1 })
}

function openPayment() {
  if (!payment.value) return
  const appUrl = payment.value.payment_url_app
  const webUrl = payment.value.payment_url
  if (!payment.value.sandbox && appUrl) {
    window.location.href = appUrl
    return
  }
  const width = 760
  const height = 580
  const left = Math.max(0, Math.round(window.screenX + (window.outerWidth - width) / 2))
  const top = Math.max(0, Math.round(window.screenY + (window.outerHeight - height) / 2))
  const win = window.open(webUrl, 'linepay', `left=${left},top=${top},width=${width},height=${height}`)
  if (!win) {
    toastStore.error('請允許快顯視窗以開啟付款頁')
    return
  }
  payWin.value = win
}

function cancelPay() {
  orderStore.stopPolling()
  qrDataUrl.value = ''
  orderStore.payment = null
  if (payWin.value) {
    payWin.value.close()
    payWin.value = null
  }
}

watch(() => order.value?.status, (status) => {
  if (status === 'paid' && payWin.value) {
    payWin.value.close()
    payWin.value = null
  }
})

onMounted(() => orderStore.loadDetail(route.params.id))
onUnmounted(() => {
  orderStore.stopPolling()
  if (payWin.value) payWin.value.close()
})
</script>

<style scoped>
.detail-page {
  position: relative;
  max-width: 960px;
  min-height: 65vh;
  margin: 0 auto;
  padding-bottom: 48px;
}
.detail-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--shop-border);
}
.back-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 16px;
  color: var(--shop-text-muted);
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: color .2s;
}
.back-link:hover { color: var(--shop-primary); }
.back-link .material-symbols-outlined { font-size: 18px; }
.detail-header h1 {
  margin: 0 0 6px;
  color: var(--shop-text);
  font-family: 'Sora', sans-serif;
  font-size: clamp(28px, 5vw, 40px);
  letter-spacing: -.04em;
}
.detail-header p {
  margin: 0;
  color: var(--shop-text-muted);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: .05em;
}
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 14px;
  border: 1px solid var(--shop-border);
  border-radius: 999px;
  background: var(--shop-glass);
  color: var(--shop-text-muted);
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}
.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: currentColor;
}
.status-badge.pending,
.status-badge.cancelled { color: var(--shop-error); }
.status-badge.paid,
.status-badge.shipped { color: var(--shop-primary); }
.panel,
.state-card {
  border-radius: 16px;
  box-shadow: 0 16px 36px rgba(0, 0, 0, .18);
}
.progress-panel { margin-bottom: 24px; padding: 26px 20px; }
.timeline { display: flex; align-items: flex-start; }
.timeline-step {
  position: relative;
  flex: 1;
  color: var(--shop-text-muted);
  font-size: 11px;
  font-weight: 700;
  text-align: center;
}
.timeline-step::before {
  position: absolute;
  top: 23px;
  right: 50%;
  width: 100%;
  height: 2px;
  background: var(--shop-surface-highest);
  content: '';
}
.timeline-step:first-child::before { display: none; }
.timeline-step.reached::before { background: var(--shop-primary-strong); }
.timeline-dot {
  position: relative;
  z-index: 1;
  display: grid;
  width: 48px;
  height: 48px;
  margin: 0 auto 12px;
  place-items: center;
  border: 4px solid var(--shop-surface-low);
  border-radius: 50%;
  background: var(--shop-surface-highest);
  color: var(--shop-text-muted);
}
.timeline-dot .material-symbols-outlined { font-size: 21px; }
.timeline-step.done,
.timeline-step.active { color: var(--shop-primary); }
.timeline-step.done .timeline-dot,
.timeline-step.active .timeline-dot {
  background: var(--shop-primary);
  color: var(--shop-on-primary);
}
.timeline-step.active .timeline-dot { box-shadow: 0 0 18px color-mix(in srgb, var(--shop-primary) 35%, transparent); }
.cancelled-panel {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  padding: 22px;
  color: var(--shop-error);
}
.cancelled-panel > .material-symbols-outlined { font-size: 36px; }
.cancelled-panel h2 { margin: 0 0 4px; color: var(--shop-error); font-size: 18px; }
.cancelled-panel p { margin: 0; color: var(--shop-text-muted); font-size: 13px; }
.info-grid {
  display: grid;
  gap: 16px;
  margin-bottom: 24px;
}
.info-card {
  padding: 22px;
  transition: border-color .2s;
}
.info-card:hover { border-color: color-mix(in srgb, var(--shop-primary) 35%, transparent); }
.info-card h2,
.section-heading h2 {
  margin: 0;
  color: var(--shop-text);
  font-family: 'Sora', sans-serif;
  font-size: 18px;
}
.info-card h2 {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 18px;
}
.info-card h2 .material-symbols-outlined { color: var(--shop-primary); }
.info-card dl { margin: 0; }
.info-card dl > div {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 11px 0;
  border-bottom: 1px solid var(--shop-border);
}
.info-card dl > div:last-child { border-bottom: 0; }
.info-card dt {
  flex: 0 0 auto;
  color: var(--shop-text-muted);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .06em;
}
.info-card dd {
  margin: 0;
  color: var(--shop-text);
  font-size: 13px;
  text-align: right;
  white-space: pre-wrap;
}
.items-panel { overflow: hidden; }
.section-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 20px 22px;
  border-bottom: 1px solid var(--shop-border);
  background: var(--shop-glass);
}
.section-heading > span {
  color: var(--shop-text-muted);
  font-size: 12px;
}
.table-wrap { overflow-x: auto; }
table {
  width: 100%;
  min-width: 620px;
  border-collapse: collapse;
}
th,
td {
  padding: 16px;
  border-bottom: 1px solid var(--shop-border);
  color: var(--shop-text);
  font-size: 13px;
  text-align: right;
}
th {
  background: color-mix(in srgb, var(--shop-surface-highest) 20%, transparent);
  color: var(--shop-text-muted);
  font-size: 11px;
  letter-spacing: .06em;
}
th:first-child,
td:first-child { text-align: left; }
th:nth-child(3),
td:nth-child(3) { text-align: center; }
.item-cell {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 250px;
  font-weight: 600;
}
.thumb {
  display: grid;
  flex: 0 0 64px;
  width: 64px;
  height: 64px;
  place-items: center;
  border: 1px solid var(--shop-border);
  border-radius: 9px;
  background: var(--shop-surface-lowest);
  color: var(--shop-text-muted);
  overflow: hidden;
}
.thumb img { width: 100%; height: 100%; object-fit: cover; }
.subtotal { color: var(--shop-primary); font-weight: 700; }
.totals {
  display: grid;
  justify-content: end;
  gap: 12px;
  padding: 22px;
  background: var(--shop-glass);
}
.totals > div {
  display: flex;
  justify-content: space-between;
  gap: 40px;
  width: min(100%, 280px);
  color: var(--shop-text-muted);
  font-size: 12px;
}
.totals strong { color: var(--shop-text); }
.grand-total {
  padding-top: 14px;
  border-top: 1px solid var(--shop-border);
  font-size: 15px !important;
}
.grand-total strong { color: var(--shop-primary); font-family: 'Sora', sans-serif; font-size: 19px; }
.action-area {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  flex-wrap: wrap;
  padding-top: 20px;
}
.action-title {
  width: 100%;
  color: var(--shop-text-muted);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: .06em;
}
.pay-method-btn {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  border: 1px solid var(--shop-border);
  border-radius: 12px;
  background: var(--shop-surface-highest);
  color: var(--shop-text);
  font: inherit;
  cursor: pointer;
  transition: border-color .2s, background .2s, color .2s, transform .2s;
}
.pay-method-btn:hover {
  border-color: var(--shop-primary);
  background: rgba(117, 255, 158, .12);
  color: var(--shop-primary);
}
.pay-method-btn:disabled { cursor: wait; opacity: .6; }
.pay-method-btn .material-symbols-outlined { font-size: 26px; color: var(--shop-primary); }
.pm-copy {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
  text-align: left;
}
.pm-name {
  font-size: 14px;
  font-weight: 800;
}
.pm-desc {
  color: var(--shop-text-muted);
  font-size: 11px;
}
.payment-panel { margin-top: 24px; padding: 24px; }
.payment-panel h2 {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0 0 20px;
  color: var(--shop-text);
  font-family: 'Sora', sans-serif;
  font-size: 18px;
}
.payment-panel h2 .material-symbols-outlined { color: var(--shop-primary); }
.payment-body {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 32px;
  flex-wrap: wrap;
}
.qr-wrap {
  display: grid;
  width: 240px;
  height: 240px;
  flex: 0 0 240px;
  place-items: center;
  border: 1px solid var(--shop-border);
  border-radius: 14px;
  background: #fff;
  padding: 8px;
}
.qr-wrap img { width: 100%; height: 100%; }
.payment-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  max-width: 300px;
  text-align: center;
}
.payment-tip { margin: 0; color: var(--shop-text-muted); font-size: 13px; line-height: 1.7; }
.payment-tip strong { color: var(--shop-text); }
.sandbox-hint {
  margin: 0;
  padding: 10px 14px;
  border: 1px solid color-mix(in srgb, var(--shop-error) 45%, transparent);
  border-radius: 10px;
  background: color-mix(in srgb, var(--shop-error) 10%, transparent);
  color: var(--shop-error);
  font-size: 12px;
  line-height: 1.6;
}
.waiting-hint {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  color: var(--shop-text-muted);
  font-size: 12px;
}
.cancel-pay { margin: 0; }
.pay-button,
.secondary-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 10px;
  font: inherit;
  font-size: 12px;
  font-weight: 800;
  text-decoration: none;
  cursor: pointer;
}
.pay-button {
  padding: 14px 28px;
  border: 1px solid var(--shop-primary);
  background: var(--shop-primary);
  box-shadow: 0 0 22px color-mix(in srgb, var(--shop-primary) 25%, transparent);
  color: var(--shop-on-primary);
}
.pay-button:hover { background: var(--shop-primary-strong); }
.pay-button:disabled { cursor: wait; opacity: .6; }
.secondary-button {
  margin-top: 8px;
  padding: 10px 16px;
  border: 1px solid var(--shop-border);
  background: var(--shop-surface-highest);
  color: var(--shop-text);
}
.state-card {
  display: grid;
  min-height: 260px;
  padding: 40px 20px;
  place-items: center;
  align-content: center;
  gap: 12px;
  color: var(--shop-text-muted);
  text-align: center;
}
.state-card h1 { margin: 0; color: var(--shop-text); font-size: 22px; }
.state-icon { color: var(--shop-primary); font-size: 48px; }
@media (min-width: 760px) {
  .info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; }
}
@media (max-width: 620px) {
  .detail-header { align-items: flex-start; flex-direction: column; }
  .timeline {
    align-items: stretch;
    flex-direction: column;
    gap: 0;
    padding-left: 4px;
  }
  .timeline-step {
    display: grid;
    grid-template-columns: 48px 1fr;
    align-items: center;
    gap: 14px;
    min-height: 76px;
    text-align: left;
  }
  .timeline-step::before {
    top: -14px;
    right: auto;
    left: 23px;
    width: 2px;
    height: 28px;
  }
  .timeline-dot { margin: 0; }
  .info-card dl > div { align-items: flex-start; flex-direction: column; gap: 5px; }
  .info-card dd { text-align: left; }
  .totals { justify-content: stretch; }
  .totals > div { width: 100%; }
  .action-area,
  .pay-button { width: 100%; }
}
</style>
