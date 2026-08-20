<template>
  <div v-if="requiresAuth" class="layout">
    <aside class="sidebar">
      <div class="logo"><i class="fas fa-store"></i>SHOP 後台</div>
      <nav>
        <router-link to="/"><i class="fas fa-tachometer-alt"></i>儀表板</router-link>
        <router-link to="/point-order"><i class="fas fa-plus-circle"></i>新增訂單</router-link>
        <router-link to="/orders"><i class="fas fa-shopping-cart"></i>訂單管理</router-link>
        <router-link to="/products"><i class="fas fa-utensils"></i>菜單管理</router-link>
        <router-link to="/products/add"><i class="fas fa-plus-circle"></i>新增菜單</router-link>
        <router-link to="/accounts"><i class="fas fa-user-cog"></i>後台帳號</router-link>
        <router-link to="/marquee"><i class="fas fa-scroll"></i>跑馬燈</router-link>
        <router-link to="/settings"><i class="fas fa-chair"></i>桌數設定</router-link>
        <router-link to="/account" class="nav-account"><i class="fas fa-user-shield"></i>帳號設定</router-link>
      </nav>
      <div class="user-section">
        <div class="username"><i class="fas fa-user-circle"></i> {{ authStore.admin?.username }}</div>
        <a href="#" class="logout" @click.prevent="logout"><i class="fas fa-sign-out-alt"></i> 登出</a>
      </div>
    </aside>
    <main class="main-content">
      <router-view />
    </main>
  </div>
  <router-view v-else />
</template>

<script>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from './api/index.js'
import { authStore } from './store/auth.js'

export default {
  name: 'App',
  setup() {
    const route = useRoute()
    const router = useRouter()
    const requiresAuth = computed(() => route.meta.requiresAuth)
    authStore.fetch()

    async function logout() {
      await api.logout()
      authStore.clear()
      router.push('/login')
    }

    return { authStore, requiresAuth, logout }
  },
}
</script>
