<template>
  <article class="flex flex-col rounded-xl overflow-hidden border border-outline-variant bg-surface-container-lowest">
    <div class="w-full aspect-[4/3] bg-surface-variant overflow-hidden">
        <img v-if="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
        <div v-else class="w-full h-full flex items-center justify-center">
          <span class="material-symbols-outlined text-4xl text-on-surface-variant">restaurant_menu</span>
        </div>
      </div>
    <div class="p-4 flex flex-col gap-1.5">
      <h2 class="font-headline-md text-headline-md text-on-surface">{{ product.name }}</h2>
      <p class="font-body-md text-body-md text-on-surface-variant line-clamp-2">{{ product.description }}</p>
      <div class="flex justify-between items-center mt-3">
        <span class="font-price-display text-price-display text-on-surface">NT$ {{ money(product.price) }}</span>
        <div v-if="qty > 0" class="flex items-center bg-surface-container-high rounded-lg overflow-hidden border border-outline-variant">
          <button type="button" class="p-2 text-on-surface-variant hover:bg-surface-variant transition-colors active:scale-90 duration-150" aria-label="減少數量" @click="decr">
            <span class="material-symbols-outlined" style="font-size: 18px;">remove</span>
          </button>
          <span class="font-label-lg text-label-lg text-on-surface px-3">{{ qty }}</span>
          <button type="button" class="p-2 text-on-surface-variant hover:bg-surface-variant transition-colors active:scale-90 duration-150" aria-label="增加數量" @click="incr">
            <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
          </button>
        </div>
        <button v-else type="button" class="bg-surface-container-high text-on-surface px-4 py-2 rounded-lg flex items-center gap-1 font-label-lg text-label-lg hover:bg-outline-variant transition-colors active:scale-95 duration-150" @click="incr">
          <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
          加入
        </button>
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { useOrderStore } from '../store/order.js'
import { useToastStore } from '../store/toast.js'
import { money } from '../utils/format.js'

const props = defineProps({
  product: { type: Object, required: true },
})

const orderStore = useOrderStore()
const toastStore = useToastStore()

const itemIndex = computed(() =>
  orderStore.items.findIndex(i => i.product_id === props.product.id)
)
const qty = computed(() =>
  itemIndex.value >= 0 ? orderStore.items[itemIndex.value].quantity : 0
)

function incr() {
  const r = itemIndex.value >= 0
    ? orderStore.changeQty(itemIndex.value, 1)
    : orderStore.add(props.product)
  if (r.ok && r.message) toastStore.success(r.message)
}

function decr() {
  const r = itemIndex.value >= 0
    ? orderStore.changeQty(itemIndex.value, -1)
    : { ok: false }
  if (!r.ok) toastStore.error('數量已到最小')
}
</script>