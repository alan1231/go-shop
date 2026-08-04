<template>
  <div class="auth-page">
    <div class="auth-box">
      <h1><i class="fas fa-store" style="color:#4CAF50;"></i> SHOP</h1>
      <p class="subtitle">會員登入</p>
      <div v-if="error" class="error-msg"><i class="fas fa-exclamation-circle"></i> {{ error }}</div>
      <div class="oauth-buttons">
        <a :href="googleUrl" class="btn btn-google">
          <svg viewBox="0 0 48 48" width="18" height="18" aria-hidden="true">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
          </svg>
          使用 Google 登入
        </a>
        <a :href="lineUrl" class="btn btn-line"><i class="fab fa-line"></i> 使用 LINE 登入</a>
      </div>
      <div class="divider"><span>或使用帳號登入</span></div>
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label>帳號或 Email</label>
          <input type="text" v-model="username" required placeholder="使用者名稱或 Email" />
        </div>
        <div class="form-group">
          <label>密碼</label>
          <input type="password" v-model="password" required placeholder="••••••••" />
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;" :disabled="loading">
          {{ loading ? '登入中...' : '登入' }}
        </button>
      </form>
      <p class="auth-link">還沒有帳號？<router-link to="/register">立即註冊</router-link></p>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'
import { buildOAuthUrl } from '../api/oauth.js'
import { userStore } from '../store/user.js'
export default {
  data() { return { username: '', password: '', error: '', loading: false } },
  computed: {
    googleUrl() { return buildOAuthUrl('google') },
    lineUrl() { return buildOAuthUrl('line') },
  },
  methods: {
    async handleLogin() {
      this.loading = true; this.error = ''
      const res = await api.login(this.username, this.password)
      if (res.success) {
        userStore.set(res.data.user)
        this.$router.push(this.$route.query.redirect || '/')
      } else {
        this.error = res.message
      }
      this.loading = false
    },
  },
}
</script>

<style scoped>
.auth-page { display: flex; align-items: center; justify-content: center; min-height: 80vh; }
.auth-box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); width: 380px; text-align: center; }
.auth-box h1 { font-size: 22px; margin-bottom: 4px; }
.subtitle { color: #888; font-size: 14px; margin-bottom: 24px; }
.error-msg { background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 18px; }
.form-group { text-align: left; margin-bottom: 16px; }
.form-group label { display: block; font-weight: 600; font-size: 13px; color: #444; margin-bottom: 4px; }
.form-group input { width: 100%; padding: 10px 12px; border: 1px solid #d0d5dd; border-radius: 6px; font-size: 14px; outline: none; }
.form-group input:focus { border-color: #4CAF50; }
.auth-link { margin-top: 18px; font-size: 13px; color: #888; }
.auth-link a { color: #4CAF50; text-decoration: none; font-weight: 600; }
.oauth-buttons { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
.btn-google { background: #fff; color: #444; border: 1px solid #ddd; justify-content: center; }
.btn-google:hover { background: #f8f8f8; }
.btn-line { background: #06C755; color: #fff; justify-content: center; }
.btn-line:hover { background: #05b44c; }
.divider { display: flex; align-items: center; gap: 12px; color: #aaa; font-size: 12px; margin-bottom: 20px; }
.divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #eee; }

@media (max-width: 768px) {
  .auth-box { width: 100%; max-width: 380px; padding: 28px 20px; }
}
</style>
