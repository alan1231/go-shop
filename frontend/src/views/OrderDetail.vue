<template>
  <div class="min-h-screen bg-background flex flex-col relative overflow-x-hidden">
    <header class="fixed top-0 left-0 right-0 mx-auto w-full max-w-md z-50 bg-surface pt-safe md:top-[8px] md:rounded-t-[8px]">
      <div class="h-16 flex justify-between items-center px-container-margin border-b border-outline-variant">
        <div class="flex items-center gap-xs">
          <button type="button" aria-label="返回" class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-surface-variant transition-colors" @click="goMenu">
            <span class="material-symbols-outlined">arrow_back</span>
          </button>
          <h1 class="font-headline-md text-headline-md text-on-surface">訂單狀態</h1>
        </div>
        <span class="bg-surface-container-high px-sm py-xs rounded-full font-label-lg text-label-lg text-on-surface">{{ dineLabel }}</span>
      </div>
      <Marquee />
    </header>

    <main class="flex-1 mx-auto w-full max-w-md pb-[calc(110px+env(safe-area-inset-bottom,20px))] px-container-margin pt-md flex flex-col gap-lg" :class="hasMarquee ? 'mt-[calc(6rem+env(safe-area-inset-top,0px))] md:mt-[6rem]' : 'mt-[calc(4rem+env(safe-area-inset-top,0px))] md:mt-[4rem]'">
      <div v-if="loading" class="py-16 text-center font-body-md text-body-md text-on-surface-variant">載入訂單中...</div>
      <div v-else-if="!order" class="py-16 text-center font-body-md text-body-md text-on-surface-variant">訂單不存在</div>

      <template v-else>
        <section class="status-card px-md pt-lg pb-md rounded-2xl flex flex-col items-center text-center" :class="statusClass">
          <span class="material-symbols-outlined status-icon mb-sm" :class="{ 'is-active': isCookingStatus }" style="font-size: 48px;">{{ statusIcon }}</span>
          <h2 class="font-headline-lg text-headline-lg mb-xs">{{ statusTitle }}</h2>
          <p class="font-body-md text-body-md opacity-80 mb-md max-w-[280px]">{{ statusSub }}</p>
        </section>

        <section v-if="order.status === 'pending' && !payment" class="bg-surface-bright rounded-xl border border-outline-variant p-md flex flex-col gap-sm">
          <h2 class="font-headline-md text-headline-md text-on-surface mb-xs flex items-center gap-xs">
            <span class="material-symbols-outlined text-on-surface-variant" style="font-size: 20px;">payments</span>
            付款方式
          </h2>
          <div class="w-full h-14 rounded-lg bg-surface-container border border-outline-variant flex items-center justify-between px-md">
            <span class="flex items-center gap-sm">
              <span class="material-symbols-outlined text-on-surface-variant" style="font-size: 24px;">storefront</span>
              <span class="flex flex-col items-start">
                <span class="font-label-lg text-label-lg text-on-surface">請至櫃檯結帳</span>
                <span class="font-label-sm text-label-sm text-on-surface-variant">於餐廳櫃檯以現金付款</span>
              </span>
            </span>
          </div>
          <button type="button" class="w-full h-14 rounded-lg bg-[#06C755] text-white border border-[#06C755] flex items-center justify-between px-md hover:opacity-90 active:scale-[0.98] transition-all duration-150 disabled:opacity-60 disabled:pointer-events-none" :disabled="paying" @click="pay('linepay')">
            <span class="flex items-center gap-sm">
              <span class="material-symbols-outlined" style="font-size: 24px;">brand_awareness</span>
              <span class="flex flex-col items-start">
                <span class="font-label-lg text-label-lg">LINE Pay 線上支付</span>
                <span class="font-label-sm text-label-sm text-white/85">線上支付</span>
              </span>
            </span>
            <span class="material-symbols-outlined" style="font-size: 20px;">chevron_right</span>
          </button>
        </section>

        <section v-if="payment" class="bg-surface-bright rounded-xl border border-outline-variant p-md flex flex-col items-center gap-sm">
          <h2 class="font-headline-md text-headline-md text-on-surface flex items-center gap-xs self-start">
            <span class="material-symbols-outlined text-tertiary" style="font-size: 20px;">qr_code_2</span>
            LINE Pay 付款
          </h2>
          <p class="font-body-md text-body-md text-on-surface-variant text-center">
            請點下方按鈕前往 LINE Pay 完成付款。
          </p>
          <div v-if="payment.sandbox" class="w-full rounded-lg bg-surface-container border border-outline-variant px-md py-sm flex items-center gap-sm">
            <span class="material-symbols-outlined text-on-surface-variant" style="font-size: 20px;">info</span>
            <span class="font-body-sm text-body-sm text-on-surface-variant">目前為沙箱測試環境，請於開啟的網頁中完成付款。</span>
          </div>
          <button type="button" class="w-full h-12 bg-[#06C755] text-white rounded-lg font-headline-md text-headline-md hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-xs" @click="openPayment">
            <span class="material-symbols-outlined" style="font-size: 20px;">open_in_new</span>
            {{ payment.sandbox ? '前往 LINE Pay 付款' : '開啟 LINE Pay 付款' }}
          </button>
          <button type="button" class="font-label-md text-label-md text-on-surface-variant pb-sm hover:text-error transition-colors flex items-center gap-xs" @click="cancelPay">
            <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
            取消付款
          </button>
        </section>

        <section class="bg-surface-bright rounded-xl border border-outline-variant p-md">
          <h2 class="font-headline-md text-headline-md text-on-surface mb-sm flex items-center gap-xs">
            <span class="material-symbols-outlined text-on-surface-variant" style="font-size: 20px;">receipt_long</span>
            訂單資訊
          </h2>
          <dl class="flex flex-col gap-sm">
            <div class="flex justify-between gap-4">
              <dt class="font-label-md text-label-md text-on-surface-variant shrink-0">訂單編號</dt>
              <dd class="font-body-md text-body-md text-on-surface">#ORD-{{ order.id }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="font-label-md text-label-md text-on-surface-variant shrink-0">建立時間</dt>
              <dd class="font-body-md text-body-md text-on-surface">{{ formatDate(order.created_at) }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="font-label-md text-label-md text-on-surface-variant shrink-0">用餐方式</dt>
              <dd class="font-body-md text-body-md text-on-surface">{{ orderTypeLabel }}<template v-if="order.order_type === 'dine_in' && order.table_number"> · 第 {{ order.table_number }} 桌</template></dd>
            </div>
            <div v-if="order.member_remark" class="flex justify-between gap-4">
              <dt class="font-label-md text-label-md text-on-surface-variant shrink-0">備註</dt>
              <dd class="font-body-md text-body-md text-on-surface text-right whitespace-pre-wrap">{{ order.member_remark }}</dd>
            </div>
          </dl>
        </section>

        <section class="bg-surface-bright rounded-xl border border-outline-variant p-md">
          <h2 class="font-headline-md text-headline-md text-on-surface mb-sm flex items-center gap-xs">
            <span class="material-symbols-outlined text-on-surface-variant" style="font-size: 20px;">fastfood</span>
            餐點明細
          </h2>
          <ul class="flex flex-col gap-md">
            <li v-for="item in order.items" :key="item.id" class="flex gap-sm pb-md border-b border-outline-variant/30">
              <div class="w-16 h-16 rounded-lg overflow-hidden shrink-0 bg-surface-variant">
                <img v-if="item.image" :src="imageUrl(item.image)" :alt="item.name" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <span class="material-symbols-outlined text-on-surface-variant">restaurant_menu</span>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex justify-between gap-2">
                  <h3 class="font-label-lg text-label-lg text-on-surface truncate">{{ item.name }}</h3>
                  <span class="font-price-display text-price-display-sm text-on-surface shrink-0">NT$ {{ money(item.price * item.quantity) }}</span>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">NT$ {{ money(item.price) }} × {{ item.quantity }}</p>
              </div>
            </li>
          </ul>
          <div class="flex justify-between items-center pt-md mt-sm">
            <span class="font-body-md text-body-md text-on-surface-variant">共 {{ totalQuantity }} 項餐點</span>
            <span class="font-price-display text-price-display text-primary">NT$ {{ money(order.total_amount) }}</span>
          </div>
        </section>

        <section v-if="order.receiver_phone" class="bg-surface-bright rounded-xl border border-outline-variant p-md">
          <h2 class="font-headline-md text-headline-md text-on-surface mb-sm flex items-center gap-xs">
            <span class="material-symbols-outlined text-on-surface-variant" style="font-size: 20px;">phone_iphone</span>
            聯絡資訊
          </h2>
          <p class="font-body-md text-body-md text-on-surface">{{ order.receiver_phone }}</p>
        </section>
      </template>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOrderStore } from '../store/order.js'
import { useToastStore } from '../store/toast.js'
import { useSiteStore } from '../store/site.js'
import { formatDate as formatDateValue, imageUrl, money } from '../utils/format.js'
import Marquee from '../components/Marquee.vue'

const route = useRoute()
const router = useRouter()
const orderStore = useOrderStore()
const toastStore = useToastStore()
const site = useSiteStore()

const order = computed(() => orderStore.detail)
const loading = computed(() => orderStore.detailLoading)
const payment = computed(() => orderStore.payment)
const paying = computed(() => orderStore.paying)
const payWin = ref(null)
const hasMarquee = computed(() => site.marqueeText !== '')

const totalQuantity = computed(() =>
  (order.value?.items || []).reduce((sum, item) => sum + Number(item.quantity), 0)
)

const orderTypeLabel = computed(() => {
  if (!order.value) return '—'
  return order.value.order_type === 'takeout' ? '外帶' : '內用'
})

const dineLabel = computed(() => {
  if (order.value?.order_type === 'takeout') return '外帶'
  return `內用 · 第 ${order.value?.table_number || '—'} 桌`
})

const statusClass = computed(() => {
  const s = order.value?.status
  if (s === 'cancelled') return 'bg-error-container text-on-error-container'
  if (s === 'completed') return 'bg-tertiary-container text-on-tertiary-container'
  return 'bg-primary-container text-on-primary-container'
})

const statusIcon = computed(() => {
  const s = order.value?.status
  if (s === 'cancelled') return 'cancel'
  if (s === 'completed') return 'task_alt'
  return 'cooking'
})

const isCookingStatus = computed(() => {
  const s = order.value?.status
  return s !== 'completed' && s !== 'cancelled'
})

const statusTitle = computed(() => {
  const s = order.value?.status
  if (s === 'completed') return '已完成'
  if (s === 'cancelled') return '已取消'
  return '製作中'
})

const statusSub = computed(() => {
  const s = order.value?.status
  if (s === 'cancelled') return '這筆訂單已取消，不會繼續付款或製作。'
  if (s === 'completed') return '訂單已完成，感謝您的用餐！'
  return '您的餐點正在製作中，請稍候。'
})

function formatDate(value) {
  return formatDateValue(value, { separator: '/', time: true, empty: '—' })
}

function goMenu() {
  router.push('/')
}

async function pay(method) {
  if (!order.value) return
  const res = await orderStore.pay(order.value.id, method)
  if (!res.success) {
    toastStore.error(res.message)
    return
  }
  if (res.data?.payment_access_token) {
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

async function openPayment() {
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

onMounted(() => {
  if (orderStore.isJustPlaced(route.params.id)) {
    orderStore.loadDetail(route.params.id)
  } else {
    router.replace('/')
  }
})
onUnmounted(() => {
  orderStore.stopPolling()
  if (payWin.value) payWin.value.close()
})
</script>

<style scoped>
.status-card {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}
.status-icon.is-active {
  animation: sizzle 1.6s ease-in-out infinite;
  filter: drop-shadow(0 4px 14px rgba(0, 0, 0, 0.28));
}
@keyframes sizzle {
  0%, 100% { transform: scale(1) rotate(0deg) translateY(0); opacity: 1; }
  50% { transform: scale(1.14) rotate(-6deg) translateY(-3px); opacity: 0.82; }
}
</style>