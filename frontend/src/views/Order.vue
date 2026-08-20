<template>
  <div class="min-h-screen bg-background flex flex-col relative overflow-x-hidden">
    <header class="fixed top-0 left-0 right-0 mx-auto w-full max-w-md z-50 bg-surface pt-safe md:top-[8px] md:rounded-t-[8px]">
      <div class="h-16 flex justify-between items-center px-container-margin border-b border-outline-variant">
        <div class="flex items-center gap-xs">
          <button type="button" aria-label="返回" class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-surface-variant transition-colors" @click="goBack">
            <span class="material-symbols-outlined">arrow_back</span>
          </button>
          <h1 class="font-headline-md text-headline-md text-on-surface">確認餐點</h1>
        </div>
        <span class="bg-surface-container-high px-sm py-xs rounded-full font-label-lg text-label-lg text-on-surface">{{ dineLabel }}</span>
      </div>
      <Marquee />
    </header>

    <main class="flex-1 mx-auto w-full max-w-md pb-[calc(110px+env(safe-area-inset-bottom,20px))] px-container-margin pt-md flex flex-col gap-lg" :class="hasMarquee ? 'mt-[calc(6rem+env(safe-area-inset-top,0px))] md:mt-[6rem]' : 'mt-[calc(4rem+env(safe-area-inset-top,0px))] md:mt-[4rem]'">
      <div v-if="!items.length" class="py-16 text-center font-body-md text-body-md text-on-surface-variant">尚未加入餐點</div>

      <section v-else class="flex flex-col gap-md">
        <div v-for="(item, i) in items" :key="item.product_id" class="flex gap-md pb-md border-b border-outline-variant/30">
          <div class="w-20 h-20 rounded-lg overflow-hidden shrink-0 bg-surface-variant">
            <img v-if="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center">
              <span class="material-symbols-outlined text-on-surface-variant">restaurant_menu</span>
            </div>
          </div>
          <div class="flex-1 flex flex-col justify-between">
            <div>
              <h3 class="font-headline-md text-headline-md text-on-surface">{{ item.name }}</h3>
              <p v-if="item.remark" class="font-body-md text-body-md text-on-surface-variant mt-1">{{ item.remark }}</p>
            </div>
            <div class="flex justify-between items-center mt-2">
              <span class="font-price-display text-price-display text-on-surface">NT$ {{ money(item.price) }}</span>
              <div class="flex items-center bg-surface-container px-xs py-xs rounded-full gap-1">
                <button type="button" class="w-6 h-6 flex items-center justify-center text-on-surface-variant hover:bg-surface-variant rounded-full transition-colors" aria-label="減少數量" @click="change(i, -1)"><span class="material-symbols-outlined" style="font-size: 16px;">remove</span></button>
                <span class="font-label-lg text-label-lg text-on-surface w-7 text-center">{{ item.quantity }}</span>
                <button type="button" class="w-6 h-6 flex items-center justify-center text-on-surface-variant hover:bg-surface-variant rounded-full transition-colors" aria-label="增加數量" @click="change(i, 1)"><span class="material-symbols-outlined" style="font-size: 16px;">add</span></button>
                <button type="button" class="w-6 h-6 flex items-center justify-center text-error hover:bg-error-container rounded-full transition-colors" aria-label="移除" @click="orderStore.remove(i)"><span class="material-symbols-outlined" style="font-size: 16px;">delete</span></button>
              </div>
            </div>
          </div>
        </div>
        <div class="flex justify-between items-center pt-xs">
          <button type="button" class="font-label-lg text-label-lg text-primary flex items-center gap-xs hover:opacity-80 transition-opacity" @click="goMenu">
            <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
            新增餐點
          </button>
          <button type="button" class="font-label-lg text-label-lg text-error flex items-center gap-xs hover:opacity-80 transition-opacity" @click="clearCart">
            <span class="material-symbols-outlined" style="font-size: 18px;">delete_outline</span>
            清空訂單
          </button>
        </div>
      </section>
    </main>

    <div class="fixed bottom-[calc(env(safe-area-inset-bottom,20px)+14px)] md:bottom-[46px] max-w-[calc(448px-32px)] w-[calc(100%-32px)] left-1/2 -translate-x-1/2 z-40 bg-inverse-surface rounded-xl shadow-[0px_4px_20px_rgba(0,0,0,0.15)] flex justify-between items-center p-3 pl-5 gap-4 border border-white/10">
      <div class="flex flex-col min-w-0 shrink">
        <span class="font-label-md text-label-md text-inverse-on-surface/80 truncate">總計 ({{ count }} 項餐點)</span>
        <span class="font-price-display text-price-display text-inverse-on-surface truncate">NT$ {{ total.toLocaleString() }}</span>
      </div>
      <button type="button" class="bg-primary text-on-primary px-6 py-3 rounded-lg font-label-lg text-label-lg flex items-center gap-2 transition-all duration-150 active:scale-95 disabled:opacity-40 disabled:pointer-events-none shrink-0" :disabled="ordering || !items.length" @click="submit">
        <template v-if="ordering">
          <span class="material-symbols-outlined animate-spin" style="font-size: 18px;">progress_activity</span>
          <span>送出中...</span>
        </template>
        <template v-else>
          <span>送出訂單</span>
          <span class="material-symbols-outlined" style="font-size: 18px;">send</span>
        </template>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useOrderStore } from '../store/order.js'
import { useToastStore } from '../store/toast.js'
import { useSiteStore } from '../store/site.js'
import { money } from '../utils/format.js'
import Marquee from '../components/Marquee.vue'

const router = useRouter()
const orderStore = useOrderStore()
const toastStore = useToastStore()
const site = useSiteStore()

const ordering = ref(false)
const hasMarquee = computed(() => site.marqueeText !== '')

const items = computed(() => orderStore.items)
const count = computed(() => orderStore.count)
const total = computed(() => orderStore.items.reduce((s, i) => s + i.price * i.quantity, 0))
const dineLabel = computed(() => {
  if (orderStore.orderType === 'takeout') return '外帶'
  return `內用 · 第 ${orderStore.tableNumber} 桌`
})

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push('/')
}

function goMenu() {
  router.push('/')
}

function clearCart() {
  orderStore.clear()
  toastStore.success('已清空訂單')
}

function change(i, delta) {
  const r = orderStore.changeQty(i, delta)
  if (!r.ok && r.message) toastStore.error(r.message)
}

async function submit() {
  if (!items.value.length) return
  ordering.value = true
  const payload = items.value.map(i => ({ product_id: i.product_id, quantity: i.quantity }))
  const res = await orderStore.placeOrder(payload, { name: '', phone: '', address: '' }, '', orderStore.tableNumber, orderStore.orderType)
  ordering.value = false
  if (res.success) {
    toastStore.success('訂單已送出')
    router.push(`/orders/${res.data.order_id}`)
  } else {
    toastStore.error(res.message)
  }
}
</script>