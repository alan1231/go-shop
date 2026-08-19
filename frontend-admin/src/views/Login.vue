<template>
  <div class="login-wrap">
    <div class="login-box">
      <div class="logo">
        <i class="fas fa-store"></i>
        <h1>管理後台</h1>
        <p>請登入您的帳號</p>
      </div>
      <div v-if="error" class="msg msg-error"><i class="fas fa-exclamation-circle"></i> {{ error }}</div>
      <form @submit.prevent="submit">
        <div class="form-group">
          <label>帳號</label>
          <input type="text" v-model="username" required placeholder="使用者名稱" autofocus />
        </div>
        <div class="form-group">
          <label>密碼</label>
          <input type="password" v-model="password" required placeholder="••••••••" />
        </div>
        <button class="btn btn-primary" style="width:100%;" :disabled="loading">
          <i class="fas fa-sign-in-alt"></i> {{ loading ? '登入中...' : '登入' }}
        </button>
      </form>
      <div class="divider"><span>或使用三方登入</span></div>
      <div class="oauth-buttons">
        <button class="oauth-button google-button" type="button" @click="startOAuth('google')">
          <i class="fab fa-google"></i> Google 登入
        </button>
        <button class="oauth-button line-button" type="button" @click="startOAuth('line')">
          <i class="fab fa-line"></i> LINE 登入
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'
import { authStore } from '../store/auth.js'
import { buildOAuthUrl } from '../api/oauth.js'

export default {
  name: 'LoginView',
  data() {
    return { username: '', password: '', error: '', loading: false }
  },
  async created() {
    if (localStorage.getItem('admin_token')) {
      const res = await api.me()
      if (res.success) {
        authStore.set(res.data)
        this.$router.replace('/')
      } else {
        authStore.clear()
      }
    }
  },
  methods: {
    async submit() {
      this.loading = true
      this.error = ''
      const res = await api.login(this.username, this.password)
      if (res.success) {
        localStorage.setItem('admin_token', res.data.token)
        authStore.set(res.data.user)
        this.$router.push('/')
      } else {
        this.error = res.message
      }
      this.loading = false
    },
    startOAuth(provider) {
      window.location.href = buildOAuthUrl(provider)
    },
  },
}
</script>

<style scoped>
.login-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1a1d29 0%, #2d3348 100%);
}
.login-box {
  background: #fff;
  border-radius: 12px;
  padding: 40px;
  width: 380px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}
.login-box .logo { text-align: center; margin-bottom: 32px; }
.login-box .logo i { font-size: 36px; color: #4CAF50; }
.login-box .logo h1 { font-size: 20px; font-weight: 700; color: #1a1d29; margin-top: 8px; }
.login-box .logo p { font-size: 13px; color: #888; margin-top: 4px; }
.divider { display: flex; align-items: center; gap: 12px; margin: 22px 0 16px; color: #999; font-size: 12px; }
.divider::before, .divider::after { flex: 1; height: 1px; background: #e5e5e5; content: ''; }
.oauth-buttons { display: grid; gap: 10px; }
.oauth-button {
  display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; border-radius: 8px;
  border: 1px solid #e0e0e0; background: #fff; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.oauth-button:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12); }
.google-button { color: #333; }
.google-button i { color: #4285F4; font-size: 18px; }
.line-button { color: #333; }
.line-button i { color: #06c755; font-size: 18px; }
</style>
