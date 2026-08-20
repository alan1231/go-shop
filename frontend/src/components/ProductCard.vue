<template>
  <article
    class="flex items-center justify-between gap-3 p-3 rounded-xl border border-outline-variant bg-surface-container-lowest cursor-pointer active:scale-[0.99] transition-all duration-150"
    @click="open = true"
  >
    <div class="flex-1 min-w-0">
      <h2 class="font-headline-md text-headline-md text-on-surface truncate">{{ product.name }}</h2>
    </div>
    <div class="flex items-center gap-1 shrink-0">
      <span class="font-price-display text-price-display text-on-surface">NT$ {{ money(product.price) }}</span>
      <span class="material-symbols-outlined text-on-surface-variant" style="font-size: 20px;">chevron_right</span>
    </div>
  </article>

  <Teleport to="body">
    <div v-if="open" class="desc-mask" @click.self="open = false">
      <div class="desc-card">
        <div v-if="product.image" class="w-full aspect-[4/3] bg-surface-variant">
          <img :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
        </div>
        <div class="p-5 flex flex-col gap-3">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="font-headline-md text-headline-md text-on-surface">{{ product.name }}</h3>
            </div>
            <button type="button" aria-label="關閉" class="flex items-center justify-center w-9 h-9 rounded-full shrink-0 text-on-surface-variant hover:bg-surface-variant transition-colors" @click="open = false">
              <span class="material-symbols-outlined">close</span>
            </button>
          </div>
          <p v-if="product.description" class="font-body-md text-body-md text-on-surface-variant whitespace-pre-wrap">{{ product.description }}</p>
          <div class="flex items-center justify-between gap-3 pt-2">
            <span class="font-price-display text-price-display text-on-surface">{{ qtyLabel }}</span>
            <div v-if="qty > 0" class="flex items-center bg-surface-container-high rounded-lg overflow-hidden border border-outline-variant">
              <button type="button" class="w-9 h-9 flex items-center justify-center text-on-surface-variant hover:bg-surface-variant transition-colors active:scale-90 duration-150" aria-label="減少數量" @click="bump(-1)">
                <span class="material-symbols-outlined leading-none" style="font-size: 18px;">remove</span>
              </button>
              <span class="font-label-lg text-label-lg text-on-surface w-8 text-center">{{ qty }}</span>
              <button type="button" class="w-9 h-9 flex items-center justify-center text-on-surface-variant hover:bg-surface-variant transition-colors active:scale-90 duration-150" aria-label="增加數量" @click="bump(1)">
                <span class="material-symbols-outlined leading-none" style="font-size: 18px;">add</span>
              </button>
            </div>
            <button v-else type="button" class="bg-primary text-on-primary px-5 py-2.5 rounded-lg flex items-center gap-1 font-label-lg text-label-lg hover:opacity-90 transition-colors active:scale-95 duration-150" @click="bump(1)">
              <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
              加入
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useOrderStore } from '../store/order.js'
import { useToastStore } from '../store/toast.js'
import { money } from '../utils/format.js'

const props = defineProps({
  product: { type: Object, required: true },
})

const open = ref(false)
const orderStore = useOrderStore()
const toastStore = useToastStore()

const itemIndex = computed(() =>
  orderStore.items.findIndex(i => i.product_id === props.product.id)
)
const qty = computed(() =>
  itemIndex.value >= 0 ? orderStore.items[itemIndex.value].quantity : 0
)
const qtyLabel = computed(() =>
  qty.value > 0 ? `已選 ${qty.value} 份` : 'NT$ ' + money(props.product.price)
)

function bump(delta) {
  if (itemIndex.value < 0) {
    if (delta > 0) {
      orderStore.add(props.product)
      toastStore.success(`「${props.product.name}」已加入訂單`)
    }
    return
  }
  const item = orderStore.items[itemIndex.value]
  if (delta < 0 && item.quantity <= 1) {
    orderStore.remove(itemIndex.value)
    return
  }
  orderStore.changeQty(itemIndex.value, delta)
}
</script>

<style scoped>
.desc-mask {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  z-index: 1000;
}
.desc-card {
  width: 100%;
  max-width: 340px;
  background: #fff;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
  max-height: 82vh;
  overflow-y: auto;
}
</style>