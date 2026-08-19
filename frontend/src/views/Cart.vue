<template>
  <div class="cart-page">
    <h1 class="cart-heading">訂單內容</h1>
    <div v-if="!cart.length" class="empty-cart glass-panel">
      <span class="material-symbols-outlined">shopping_cart</span>
      <h2>尚未加入品項</h2>
      <p>挑選喜歡的商品後，再回來完成訂單。</p>
      <router-link to="/" class="checkout-button empty-action">繼續購物</router-link>
    </div>
    <div v-else class="cart-layout">
      <div class="cart-main">
        <section>
          <h2 class="section-title">商品明細</h2>
          <div class="cart-items">
            <article v-for="(item, i) in cart" :key="item.product_id" class="cart-item glass-panel">
              <img v-if="item.image" :src="item.image" :alt="item.name" class="product-thumb" />
              <div v-else class="product-thumb product-placeholder">
                <span class="material-symbols-outlined">inventory_2</span>
              </div>
              <div class="item-info">
                <h3>{{ item.name }}</h3>
                <div class="item-price">NT$ {{ Number(item.price).toLocaleString() }}</div>
              </div>
              <div class="item-controls">
                <div class="qty-control">
                  <button type="button" aria-label="增加數量" @click="changeQty(i, 1)"><span class="material-symbols-outlined">add</span></button>
                  <span>{{ item.quantity }}</span>
                  <button type="button" aria-label="減少數量" @click="changeQty(i, -1)"><span class="material-symbols-outlined">remove</span></button>
                </div>
                <button type="button" class="remove-button" aria-label="移除商品" @click="removeItem(i)"><span class="material-symbols-outlined">delete</span></button>
              </div>
            </article>
          </div>
        </section>

        <section>
          <h2 class="section-title">用餐方式</h2>
          <div class="shipping-panel glass-panel">
            <div class="dine-toggle">
              <button type="button" class="dine-btn" :class="{ active: dineType === 'dine_in' }" @click="dineType = 'dine_in'">
                <span class="material-symbols-outlined">table_restaurant</span>內用
              </button>
              <button type="button" class="dine-btn" :class="{ active: dineType === 'takeout' }" @click="dineType = 'takeout'">
                <span class="material-symbols-outlined">takeout_dining</span>外帶
              </button>
            </div>
            <div v-if="dineType === 'dine_in'" class="field-group field-wide">
              <label for="table-number">桌號</label>
              <input id="table-number" v-model.number="tableNum" type="number" min="1" placeholder="輸入桌號（掃描桌牌 QR 已自動帶入）" />
            </div>
            <p v-else class="dine-note field-wide">外帶訂單不需桌號，建議填寫手機號碼方便取餐聯繫。</p>
            <div class="field-group field-wide">
              <label for="order-remark">訂單備註</label>
              <textarea id="order-remark" v-model="remark" rows="2" placeholder="特殊需求或備註（選填）"></textarea>
            </div>
          </div>
        </section>
      </div>

      <aside class="cart-summary glass-panel">
        <div class="summary-glow"></div>
        <h2>訂單摘要</h2>
        <div class="summary-details">
          <div><span>商品數量</span><strong>{{ totalItems }} 件</strong></div>
          <div><span>商品小計</span><strong>NT$ {{ totalPrice.toLocaleString() }}</strong></div>
          <div><span>運費</span><strong>結帳時計算</strong></div>
        </div>
        <div class="summary-total">
          <span>總金額</span>
          <strong>NT$ {{ totalPrice.toLocaleString() }}</strong>
        </div>
        <button class="checkout-button summary-checkout" type="button" :disabled="ordering" @click="checkout">
          <span>{{ ordering ? '處理中...' : '前往結帳' }}</span>
          <span class="material-symbols-outlined">arrow_forward</span>
        </button>
      </aside>
    </div>

    <div v-if="cart.length" class="mobile-checkout">
      <button class="checkout-button" type="button" :disabled="ordering" @click="checkout">
        <span>{{ ordering ? '處理中...' : `結帳 · NT$ ${totalPrice.toLocaleString()}` }}</span>
        <span class="material-symbols-outlined">arrow_forward</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api/index.js'
import { useCartStore } from '../store/cart.js'
import { useToastStore } from '../store/toast.js'
import { useOrderStore } from '../store/order.js'

const router = useRouter()
const cartStore = useCartStore()
const toastStore = useToastStore()
const orderStore = useOrderStore()

const ordering = ref(false)
const remark = ref('')
const dineType = ref(cartStore.orderType)
const tableNum = ref(cartStore.tableNumber || '')

const cart = computed(() => cartStore.items)
const totalItems = computed(() => cartStore.count)
const totalPrice = computed(() => cartStore.items.reduce((s, i) => s + i.price * i.quantity, 0))

async function changeQty(i, delta) {
  const r = await cartStore.changeQty(i, delta)
  if (!r.ok && r.message) toastStore.error(r.message)
}

async function removeItem(i) {
  await cartStore.remove(i)
}

async function loadCartImages() {
  const missing = cartStore.items.filter(item => !item.image && Number.isFinite(Number(item.product_id)))
  await Promise.all(missing.map(async item => {
    const res = await api.product(item.product_id)
    if (res.success) item.image = res.data.image
  }))
  if (missing.length) cartStore.saveGuest()
}

async function checkout() {
  if (dineType.value === 'takeout') {
    const phone = prompt('外帶請留手機號碼，方便取餐聯繫：');
    if (!phone) return;
    ordering.value = true;
    cartStore.setDine(tableNum.value, dineType.value);
    const items = cartStore.items.map(i => ({ product_id: i.product_id, quantity: i.quantity }));
    const res = await orderStore.placeOrder(items, { name: '', phone, address: '' }, remark.value, cartStore.tableNumber, cartStore.orderType);
    ordering.value = false;
    if (res.success) {
      toastStore.success('訂單已建立！');
      router.push(`/orders/${res.data.order_id}`);
    } else {
      toastStore.error(res.message);
    }
    return;
  }
  ordering.value = true;
  cartStore.setDine(tableNum.value, dineType.value);
  const items = cartStore.items.map(i => ({ product_id: i.product_id, quantity: i.quantity }));
  const res = await orderStore.placeOrder(items, { name: '', phone: '', address: '' }, remark.value, cartStore.tableNumber, cartStore.orderType);
  ordering.value = false;
  if (res.success) {
    toastStore.success('訂單已建立！');
    router.push(`/orders/${res.data.order_id}`);
  } else {
    toastStore.error(res.message);
  }
}

onMounted(async () => {
  await loadCartImages()
})
</script>

<style scoped>
.cart-page { color: var(--shop-text); padding-bottom: 24px; }
.cart-heading { margin-bottom: 28px; font-family: 'Sora', sans-serif; font-size: 32px; line-height: 1.2; letter-spacing: -0.02em; }
.cart-layout { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 32px; align-items: start; }
.cart-main { display: grid; gap: 40px; }
.section-title { margin-bottom: 18px; font-family: 'Sora', sans-serif; font-size: 24px; line-height: 1.2; }
.glass-panel { background: var(--shop-glass); border: 1px solid var(--shop-border); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
.cart-items { display: grid; gap: 14px; }
.cart-item { display: flex; align-items: center; gap: 16px; padding: 16px; border-radius: 12px; transition: background 0.25s, transform 0.25s; }
.cart-item:hover { background: var(--shop-surface-high); transform: translateY(-1px); }
.product-thumb { width: 80px; height: 80px; flex: 0 0 80px; border-radius: 8px; object-fit: cover; background: var(--shop-surface-highest); }
.product-placeholder { display: flex; align-items: center; justify-content: center; color: var(--shop-text-muted); }
.product-placeholder .material-symbols-outlined { font-size: 30px; }
.item-info { flex: 1; min-width: 0; }
.item-info h3 { overflow: hidden; color: var(--shop-text); font-size: 20px; font-weight: 600; line-height: 1.35; text-overflow: ellipsis; white-space: nowrap; }
.item-info p { margin: 3px 0 9px; color: var(--shop-text-muted); font-family: 'JetBrains Mono', monospace; font-size: 11px; letter-spacing: 0.05em; }
.item-price { color: var(--shop-primary); font-family: 'JetBrains Mono', monospace; font-size: 16px; font-weight: 500; }
.item-controls { display: flex; align-items: center; gap: 10px; }
.qty-control { display: flex; flex-direction: column; align-items: center; overflow: hidden; border: 1px solid var(--shop-border); border-radius: 8px; background: var(--shop-input); box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.5); }
.qty-control button { display: flex; width: 34px; height: 29px; align-items: center; justify-content: center; border: 0; background: transparent; color: var(--shop-text-muted); cursor: pointer; transition: color 0.2s, transform 0.2s; }
.qty-control button:first-child:hover { color: var(--shop-primary); }
.qty-control button:last-child:hover { color: var(--shop-error); }
.qty-control button:active { transform: scale(0.9); }
.qty-control .material-symbols-outlined { font-size: 17px; }
.qty-control > span { min-width: 34px; color: var(--shop-text); font-family: 'JetBrains Mono', monospace; font-size: 12px; text-align: center; }
.remove-button { display: flex; width: 36px; height: 36px; align-items: center; justify-content: center; border: 0; border-radius: 50%; background: transparent; color: var(--shop-text-muted); cursor: pointer; transition: color 0.2s, background 0.2s; }
.remove-button:hover { background: rgba(255, 180, 171, 0.12); color: var(--shop-error); }
.remove-button .material-symbols-outlined { font-size: 20px; }
.shipping-panel { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; padding: 24px; border-radius: 12px; }
.dine-toggle { display: flex; gap: 10px; grid-column: 1 / -1; }
.dine-btn { display: inline-flex; flex: 1; align-items: center; justify-content: center; gap: 8px; height: 46px; border: 1px solid var(--shop-border); border-radius: 10px; background: var(--shop-input); color: var(--shop-text-muted); font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.dine-btn .material-symbols-outlined { font-size: 20px; }
.dine-btn:hover { border-color: var(--shop-primary); color: var(--shop-text); }
.dine-btn.active { border-color: var(--shop-primary); background: color-mix(in srgb, var(--shop-primary) 14%, var(--shop-input)); color: var(--shop-primary); }
.dine-note { margin: 0; color: var(--shop-text-muted); font-size: 13px; line-height: 1.6; }
.field-wide { grid-column: 1 / -1; }
.field-group label { display: block; margin-bottom: 8px; color: var(--shop-text-muted); font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 500; letter-spacing: 0.05em; }
.field-group input, .field-group textarea { width: 100%; border: 1px solid var(--shop-border); border-radius: 8px; outline: none; background: var(--shop-input); box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.5); color: var(--shop-text); font-family: 'Hanken Grotesk', sans-serif; font-size: 15px; transition: border-color 0.2s, box-shadow 0.2s; }
.field-group input { height: 48px; padding: 0 14px; }
.field-group textarea { padding: 13px 14px; resize: vertical; }
.field-group input::placeholder, .field-group textarea::placeholder { color: #777775; }
.field-group input:focus, .field-group textarea:focus { border-color: var(--shop-primary); box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.45), 0 0 12px rgba(117, 255, 158, 0.16); }
.cart-summary { position: sticky; top: 88px; overflow: hidden; padding: 24px; border-radius: 12px; }
.summary-glow { position: absolute; top: -70px; right: -70px; width: 150px; height: 150px; border-radius: 50%; background: rgba(117, 255, 158, 0.18); filter: blur(45px); pointer-events: none; }
.cart-summary h2 { position: relative; margin-bottom: 20px; color: var(--shop-text); font-size: 20px; font-weight: 600; }
.summary-details { position: relative; display: grid; gap: 12px; padding-bottom: 18px; border-bottom: 1px solid var(--shop-border); color: var(--shop-text-muted); }
.summary-details > div { display: flex; justify-content: space-between; gap: 12px; }
.summary-details strong { color: var(--shop-text); font-weight: 400; text-align: right; }
.summary-total { position: relative; display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; padding: 20px 0 4px; font-size: 18px; font-weight: 600; }
.summary-total strong { color: var(--shop-primary); font-family: 'Sora', sans-serif; font-size: 25px; letter-spacing: -0.02em; text-align: right; }
.checkout-button { display: flex; width: 100%; min-height: 54px; align-items: center; justify-content: center; gap: 8px; border: 0; border-radius: 999px; background: linear-gradient(135deg, var(--shop-primary) 0%, var(--shop-primary-strong) 100%); box-shadow: 0 4px 15px rgba(0, 230, 118, 0.3); color: var(--shop-on-primary-fixed); font-family: 'Hanken Grotesk', sans-serif; font-size: 17px; font-weight: 600; text-decoration: none; cursor: pointer; transition: box-shadow 0.25s, transform 0.25s; }
.checkout-button:hover { box-shadow: 0 0 20px rgba(117, 255, 158, 0.55); transform: scale(1.01); }
.checkout-button:disabled { opacity: 0.55; cursor: wait; transform: none; }
.summary-checkout { margin-top: 22px; }
.mobile-checkout { display: none; }
.empty-cart { padding: 64px 24px; border-radius: 14px; text-align: center; }
.empty-cart > .material-symbols-outlined { color: var(--shop-primary); font-size: 54px; }
.empty-cart h2 { margin: 14px 0 6px; font-family: 'Sora', sans-serif; font-size: 24px; }
.empty-cart p { color: var(--shop-text-muted); }
.empty-action { max-width: 240px; margin: 24px auto 0; }

@media (max-width: 768px) {
  .cart-page { padding-bottom: 92px; }
  .cart-heading { margin-bottom: 24px; font-size: 28px; }
  .cart-layout { grid-template-columns: 1fr; gap: 40px; }
  .cart-main { gap: 36px; }
  .section-title { font-size: 23px; }
  .cart-item { gap: 12px; padding: 14px; }
  .product-thumb { width: 72px; height: 72px; flex-basis: 72px; }
  .item-info h3 { font-size: 17px; }
  .item-price { font-size: 14px; }
  .item-controls { gap: 2px; }
  .remove-button { width: 30px; }
  .shipping-panel { grid-template-columns: 1fr; padding: 20px; }
  .field-wide { grid-column: auto; }
  .cart-summary { position: static; }
  .summary-checkout { display: none; }
  .mobile-checkout { position: fixed; right: 0; bottom: 0; left: 0; z-index: 90; display: block; padding: 22px 16px 16px; background: linear-gradient(to top, var(--shop-background) 62%, rgba(var(--shop-background-rgb), 0)); }
}

@media (max-width: 420px) {
  .cart-item { align-items: flex-start; }
  .product-thumb { width: 64px; height: 64px; flex-basis: 64px; }
  .item-info h3 { font-size: 15px; }
  .item-info p { display: none; }
  .item-controls { margin-left: auto; }
  .remove-button { display: none; }
}
</style>
