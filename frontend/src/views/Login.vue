<template>
  <div class="auth-page">
    <div class="auth-box">
      <h1><i class="fas fa-store" style="color:#4CAF50;"></i> SHOP</h1>
      <p class="subtitle">會員登入</p>
      <div v-if="error" class="error-msg"><i class="fas fa-exclamation-circle"></i> {{ error }}</div>
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
export default {
  data() { return { username: '', password: '', error: '', loading: false } },
  methods: {
    async handleLogin() {
      this.loading = true; this.error = ''
      const res = await api.login(this.username, this.password)
      if (res.success) {
        this.$router.push('/')
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
</style>
