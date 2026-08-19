<template>
  <div id="layout" :class="{ 'page-home': $route.name === 'home' }">
    <nav>
      <div class="inner">
        <router-link to="/" class="logo"><i class="fas fa-store"></i> SHOP</router-link>
        <button class="nav-toggle" @click.stop="mobileOpen = !mobileOpen" aria-label="選單">
          <i class="fas" :class="mobileOpen ? 'fa-times' : 'fa-bars'"></i>
        </button>
        <div class="nav-links" :class="{ open: mobileOpen }">
          <router-link to="/"><i class="fas fa-home"></i> 首頁</router-link>
          <router-link to="/cart"><i class="fas fa-clipboard-list"></i> 訂單 ({{ cartCount }})</router-link>
        </div>
      </div>
    </nav>
    <div class="site-marquee" v-if="site.marqueeText">
      <span>{{ site.marqueeText }}</span>
    </div>
    <main>
      <router-view />
    </main>
    <footer>
      <p>&copy; 2026 SHOP</p>
    </footer>
    <Toast />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { useSiteStore } from './store/site.js'
import { useCartStore } from './store/cart.js'
import Toast from './components/Toast.vue'

const route = useRoute()
const site = useSiteStore()
const cartStore = useCartStore()

const mobileOpen = ref(false)
const cartCount = computed(() => cartStore.count)

watch(() => route.fullPath, () => {
  mobileOpen.value = false
})

onMounted(() => {
  site.init()
})

onBeforeUnmount(() => {
  site.dispose()
})
</script>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Hanken Grotesk', 'Segoe UI', Arial, sans-serif; background: var(--shop-background); color: var(--shop-text); -webkit-font-smoothing: antialiased; }
#layout { display: flex; flex-direction: column; min-height: 100vh; }

#layout > nav { position: sticky; top: 0; z-index: 100; background: rgba(var(--shop-background-rgb), 0.8); color: var(--shop-text); display: flex; align-items: center; justify-content: space-between; padding: 14px 40px; border-bottom: 1px solid var(--shop-border); box-shadow: 0 20px 50px rgba(0, 228, 117, 0.1); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
#layout > nav .inner { width: 100%; display: flex; align-items: center; justify-content: space-between; }
#layout > nav .logo { font-size: 20px; font-weight: 700; color: var(--shop-primary); text-decoration: none; }
#layout > nav .logo i { color: var(--shop-primary); margin-right: 8px; }
.nav-links { display: flex; gap: 20px; align-items: center; }
.nav-links a { color: var(--shop-text-muted); text-decoration: none; font-size: 14px; transition: color 0.2s; }
.nav-links a:hover, .nav-links a.router-link-exact-active { color: var(--shop-text); }
.nav-links { position: relative; }
.nav-toggle { display: none; background: none; border: none; color: var(--shop-text); font-size: 22px; cursor: pointer; padding: 4px 6px; }

#layout > .site-marquee { background: var(--shop-primary); color: var(--shop-on-primary); overflow: hidden; padding: 9px 0; border-bottom: 1px solid var(--shop-primary-strong); font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 700; letter-spacing: .08em; }
#layout > .site-marquee span { display: inline-block; white-space: nowrap; animation: marquee-scroll 20s linear infinite; }
@keyframes marquee-scroll {
  0% { transform: translateX(100vw); }
  100% { transform: translateX(-100%); }
}

main { flex: 1; max-width: 1200px; width: 100%; margin: 0 auto; padding: 30px 40px; }

footer { background: var(--shop-background); color: var(--shop-text-muted); text-align: center; padding: 20px; font-size: 13px; }

.btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 22px; border-radius: 8px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s; }
.btn-primary { background: var(--shop-primary); color: var(--shop-on-primary); }
.btn-primary:hover { background: var(--shop-primary-strong); }
.btn-default { background: var(--shop-surface-high); color: var(--shop-text); border: 1px solid var(--shop-border); }
.btn-default:hover { background: var(--shop-surface-highest); }
.btn-danger { background: #93000a; color: #ffdad6; }
.btn-danger:hover { background: #b4000c; }

.card { background: var(--shop-glass); border: 1px solid var(--shop-border); border-radius: 12px; padding: 24px; margin-bottom: 20px; }
.card h3 { color: var(--shop-text); }
.loader { display: inline-block; width: 28px; height: 28px; border: 2px solid var(--shop-border); border-top-color: var(--shop-primary); border-radius: 50%; animation: spin .8s linear infinite; }
.loader-sm { width: 16px; height: 16px; }
.loader-lg { width: 30px; height: 30px; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  #layout > nav { padding: 12px 16px; }
  #layout > nav .logo { font-size: 18px; }
  .page-home > footer { display: none; }
  .page-home > main { padding: 0; }
  .nav-toggle { display: block; }
  .nav-links {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    flex-direction: column;
    align-items: stretch;
    gap: 0;
    background: var(--shop-background);
    padding: 6px 16px 16px;
    box-shadow: 0 12px 24px rgba(0,0,0,0.3);
  }
  .nav-links:not(.open) { display: none; }
  .nav-links a {
    padding: 14px 4px;
    font-size: 15px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .nav-links a:last-child { border-bottom: none; }
  .nav-links a i { width: 20px; text-align: center; }
  main { padding: 20px 16px; }
}
</style>
