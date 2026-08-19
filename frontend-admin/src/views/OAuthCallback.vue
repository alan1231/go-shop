<template>
  <div class="oauth-wrap">
    <div class="oauth-box" v-if="error">
      <i class="fas fa-exclamation-circle" style="font-size:40px;color:#d32f2f;"></i>
      <h2>登入失敗</h2>
      <p>{{ error }}</p>
      <button class="btn btn-primary" style="width:100%;margin-top:14px;" @click="$router.replace('/login')">返回登入</button>
    </div>
    <div class="oauth-box" v-else>
      <i class="fas fa-spinner fa-spin" style="font-size:34px;color:#4CAF50;"></i>
      <h2>登入中...</h2>
      <p>正在驗證三方帳號，請稍候。</p>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'
import { authStore } from '../store/auth.js'
import { verifyOAuthState } from '../api/oauth.js'

export default {
  name: 'OAuthCallbackView',
  data() {
    return { error: '' }
  },
  async created() {
    const params = new URLSearchParams(window.location.search)
    const code = params.get('code')
    const state = params.get('state')
    const provider = state ? String(state).split('-')[0] : ''
    let intent = 'login'
    try {
      intent = verifyOAuthState(state)
    } catch {
      this.error = '三方登入驗證失敗，請重新嘗試'
      return
    }
    if (!code) {
      this.error = '三方登入驗證失敗，請重新嘗試'
      return
    }
    const redirectUri = window.location.origin + '/admin/auth/callback'
    if (intent === 'bind') {
      const res = await api.oauthBind(provider, code, redirectUri)
      if (res.success) {
        await authStore.fetch()
        this.$router.replace('/account')
      } else {
        this.error = res.message || '綁定失敗，請重新嘗試'
      }
      return
    }
    const res = await api.oauthLogin(provider, code, redirectUri)
    if (res.success) {
      localStorage.setItem('admin_token', res.data.token)
      authStore.set(res.data.user)
      this.$router.replace('/')
    } else {
      this.error = res.message || '登入失敗，請重新嘗試'
    }
  },
}
</script>

<style scoped>
.oauth-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1a1d29 0%, #2d3348 100%);
}
.oauth-box {
  background: #fff;
  border-radius: 12px;
  padding: 40px;
  width: 340px;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}
.oauth-box h2 { font-size: 19px; font-weight: 700; color: #1a1d29; margin-top: 14px; }
.oauth-box p { font-size: 13px; color: #888; margin-top: 6px; line-height: 1.6; }
</style>