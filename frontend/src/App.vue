<template>
  <div id="layout">
    <nav v-if="showHeader">
      <div class="inner">
        <router-link to="/" class="logo"><i class="fas fa-store"></i> SHOP</router-link>
        <div class="nav-links">
          <router-link to="/"><i class="fas fa-home"></i> 首頁</router-link>
          <router-link to="/cart"><i class="fas fa-shopping-cart"></i> 購物車 ({{ cartCount }})</router-link>
          <router-link v-if="user" to="/orders"><i class="fas fa-file-invoice"></i> 訂單</router-link>
          <span v-if="user" class="user-info"><i class="fas fa-user"></i> {{ user.username }}</span>
          <router-link v-if="!user" to="/login"><i class="fas fa-sign-in-alt"></i> 登入</router-link>
          <a v-else href="#" @click.prevent="handleLogout"><i class="fas fa-sign-out-alt"></i> 登出</a>
        </div>
      </div>
    </nav>
    <div class="marquee" v-if="showHeader && marqueeText">
      <span>{{ marqueeText }}</span>
    </div>
    <main>
      <router-view @add-to-cart="addToCart" />
    </main>
    <footer v-if="showHeader">
      <p>&copy; 2026 SHOP</p>
    </footer>
  </div>
</template>

<script>
import { api } from './api/index.js'
import { cartStore } from './store/cart.js'

export default {
  data() {
    return {
      user: null,
      marqueeText: '',
    }
  },
  computed: {
    showHeader() {
      return !['login', 'register', 'oauth-callback'].includes(this.$route.name)
    },
    cartCount() {
      return cartStore.count
    },
  },
  methods: {
    async fetchUser() {
      const res = await api.me()
      if (res.success) this.user = res.data
    },
    async fetchMarquee() {
      const res = await api.marquee()
      if (res.success) this.marqueeText = res.data.content
    },
    addToCart(product) {
      cartStore.add(product)
    },
    async handleLogout() {
      await api.logout()
      localStorage.removeItem('token')
      this.user = null
      this.$router.push('/')
    },
  },
  async created() {
    await this.fetchMarquee()
    await this.fetchUser()
    setInterval(() => this.fetchMarquee(), 30000)
  },
}
</script>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; color: #333; }
#layout { display: flex; flex-direction: column; min-height: 100vh; }

nav { position: sticky; top: 0; z-index: 100; background: #1a1d29; color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 14px 40px; }
nav .inner { width: 100%; display: flex; align-items: center; justify-content: space-between; }
nav .logo { font-size: 20px; font-weight: 700; color: #fff; text-decoration: none; }
nav .logo i { color: #4CAF50; margin-right: 8px; }
.nav-links { display: flex; gap: 20px; align-items: center; }
.nav-links a { color: #b0b3c5; text-decoration: none; font-size: 14px; transition: color 0.2s; }
.nav-links a:hover, .nav-links a.router-link-exact-active { color: #fff; }
.user-info { color: #4CAF50; font-size: 14px; font-weight: 600; }

.marquee { background: #4CAF50; color: #fff; overflow: hidden; padding: 7px 0; font-size: 13px; }
.marquee span { display: inline-block; white-space: nowrap; animation: marquee-scroll 20s linear infinite; }
@keyframes marquee-scroll {
  0% { transform: translateX(100vw); }
  100% { transform: translateX(-100%); }
}

main { flex: 1; max-width: 1200px; width: 100%; margin: 0 auto; padding: 30px 40px; }

footer { background: #1a1d29; color: #888; text-align: center; padding: 20px; font-size: 13px; }

.btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 22px; border-radius: 8px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s; }
.btn-primary { background: #4CAF50; color: #fff; }
.btn-primary:hover { background: #43a047; }
.btn-default { background: #e0e0e0; color: #444; }
.btn-default:hover { background: #d0d0d0; }
.btn-danger { background: #f44336; color: #fff; }
.btn-danger:hover { background: #d32f2f; }

.card { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); padding: 24px; margin-bottom: 20px; }
</style>
