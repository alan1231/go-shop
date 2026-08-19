<template>
  <div class="success-page">
    <div class="success-card glass-panel">
      <div class="success-icon">
        <span class="material-symbols-outlined">check_circle</span>
      </div>
      <h1>訂單已建立</h1>
      <p class="success-sub">感謝您的訂購，店家會盡快為您準備</p>

      <template v-if="order">
        <div class="success-order-no">訂單編號 #{{ order.id }}</div>
        <div class="success-meta">
          <div v-if="order.table_number"><span>桌號</span><strong>{{ order.table_number }} 號桌</strong></div>
          <div><span>金額</span><strong>NT$ {{ Number(order.total_amount).toLocaleString() }}</strong></div>
          <div><span>狀態</span><strong>{{ STATUS[order.status] || order.status }}</strong></div>
        </div>

        <div class="success-items">
          <div v-for="item in order.items" :key="item.id" class="success-item">
            <span class="item-name">{{ item.name }}</span>
            <span class="item-qty">x {{ item.quantity }}</span>
            <span class="item-price">NT$ {{ Number(item.price * item.quantity).toLocaleString() }}</span>
          </div>
        </div>
      </template>

      <div class="success-actions">
        <router-link to="/" class="checkout-button"><span class="material-symbols-outlined">home</span>回首頁</router-link>
        <router-link to="/cart" class="checkout-button checkout-ghost"><span class="material-symbols-outlined">shopping_cart</span>繼續購物</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
const order = history.state?.order || null
const STATUS = { pending: '待付款', paid: '已付款', shipped: '出貨中', completed: '已完成', cancelled: '已取消' }
</script>

<style scoped>
.success-page { display: flex; justify-content: center; padding: 40px 16px 60px; color: var(--shop-text); }
.success-card { width: 100%; max-width: 520px; padding: 40px 32px; border-radius: 16px; text-align: center; }
.glass-panel { background: var(--shop-glass); border: 1px solid var(--shop-border); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
.success-icon { display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; width: 72px; height: 72px; border-radius: 50%; background: rgba(117, 255, 158, 0.12); }
.success-icon .material-symbols-outlined { color: var(--shop-primary); font-size: 42px; }
.success-card h1 { margin-bottom: 6px; font-family: 'Sora', sans-serif; font-size: 28px; letter-spacing: -0.02em; }
.success-sub { margin-bottom: 24px; color: var(--shop-text-muted); }
.success-order-no { display: inline-block; margin-bottom: 18px; padding: 8px 18px; border: 1px solid var(--shop-border); border-radius: 999px; background: var(--shop-surface-high); color: var(--shop-primary); font-family: 'JetBrains Mono', monospace; font-size: 14px; }
.success-meta { display: grid; gap: 8px; margin-bottom: 20px; padding: 16px 20px; border: 1px solid var(--shop-border); border-radius: 12px; background: var(--shop-surface); }
.success-meta > div { display: flex; justify-content: space-between; gap: 12px; color: var(--shop-text-muted); font-size: 14px; }
.success-meta strong { color: var(--shop-text); text-align: right; }
.success-items { margin-bottom: 24px; padding: 12px 16px; border: 1px solid var(--shop-border); border-radius: 12px; text-align: left; }
.success-item { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px dashed var(--shop-border); font-size: 14px; }
.success-item:last-child { border-bottom: none; }
.item-name { flex: 1; color: var(--shop-text); }
.item-qty { color: var(--shop-text-muted); }
.item-price { color: var(--shop-text); font-family: 'JetBrains Mono', monospace; }
.success-actions { display: grid; gap: 12px; }
.checkout-button {
  display: flex; width: 100%; min-height: 52px; align-items: center; justify-content: center; gap: 8px; border: 0; border-radius: 999px;
  background: linear-gradient(135deg, var(--shop-primary) 0%, var(--shop-primary-strong) 100%);
  box-shadow: 0 4px 15px rgba(0, 230, 118, 0.3); color: var(--shop-on-primary-fixed); font-family: 'Hanken Grotesk', sans-serif;
  font-size: 16px; font-weight: 600; text-decoration: none; transition: box-shadow 0.25s, transform 0.25s;
}
.checkout-button:hover { box-shadow: 0 0 20px rgba(117, 255, 158, 0.55); transform: scale(1.01); }
.checkout-ghost { background: var(--shop-surface-high); border: 1px solid var(--shop-border); box-shadow: none; color: var(--shop-text); }
.checkout-ghost:hover { box-shadow: none; }
.checkout-button .material-symbols-outlined { font-size: 20px; }
</style>
