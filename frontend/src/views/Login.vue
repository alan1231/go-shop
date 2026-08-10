<template>
  <section class="auth-page">
    <div class="glow glow-left"></div>
    <div class="glow glow-right"></div>

    <div class="auth-stage">
      <div class="auth-card">
        <div class="brand-mark">
          <span class="material-symbols-outlined">storefront</span>
        </div>
        <h1>登入 SHOP</h1>
        <p class="subtitle">歡迎回來，繼續您的購物之旅</p>

        <div v-if="error" class="error-message" role="alert">
          <span class="material-symbols-outlined">error</span>
          {{ error }}
        </div>

        <div class="oauth-buttons">
          <button class="oauth-button google-button" type="button" @click="startOAuth('google')">
            <svg viewBox="0 0 48 48" width="20" height="20" aria-hidden="true">
              <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
              <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
              <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24s.92 7.54 2.56 10.78l7.97-6.19z"/>
              <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            Google 登入
          </button>
          <button class="oauth-button line-button" type="button" @click="startOAuth('line')">
            <i class="fab fa-line" aria-hidden="true"></i>
            LINE 登入
          </button>
        </div>

        <div class="divider"><span>或使用帳號登入</span></div>

        <form @submit.prevent="handleLogin">
          <div class="form-group">
            <label for="login-username">電子郵件／帳號</label>
            <input id="login-username" v-model.trim="username" type="text" autocomplete="username" required placeholder="輸入您的信箱或帳號" />
          </div>
          <div class="form-group">
            <label for="login-password">密碼</label>
            <div class="password-field">
              <input id="login-password" v-model="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required placeholder="輸入您的密碼" />
              <button type="button" :aria-label="showPassword ? '隱藏密碼' : '顯示密碼'" @click="showPassword = !showPassword">
                <span class="material-symbols-outlined">{{ showPassword ? 'visibility_off' : 'visibility' }}</span>
              </button>
            </div>
          </div>
          <button type="submit" class="login-button" :disabled="loading">
            <span v-if="loading" class="loader"></span>
            {{ loading ? '登入中...' : '立即登入' }}
          </button>
        </form>

        <p class="register-link">還沒有帳號？<router-link to="/register">立即註冊</router-link></p>
      </div>
    </div>

    <footer class="auth-footer">
      <strong>SHOP</strong>
      <span>© 2026 SHOP 3C RETAILERS</span>
    </footer>
  </section>
</template>

<script>
import { api } from '../api/index.js'
import { buildOAuthUrl } from '../api/oauth.js'
import { userStore } from '../store/user.js'
export default {
  data() { return { username: '', password: '', error: '', loading: false, showPassword: false } },
  methods: {
    startOAuth(provider) {
      window.location.href = buildOAuthUrl(provider, this.$route.query.redirect)
    },
    async handleLogin() {
      this.loading = true
      this.error = ''
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
.auth-page {
  position: relative;
  display: grid;
  grid-template-rows: 1fr auto;
  min-height: 100vh;
  margin: -30px -40px;
  background: var(--shop-background);
  overflow: hidden;
}
.auth-stage {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 56px 16px;
}
.glow {
  position: absolute;
  border-radius: 50%;
  background: var(--shop-primary);
  filter: blur(120px);
  pointer-events: none;
}
.glow-left {
  top: 15%;
  left: 12%;
  width: 420px;
  height: 420px;
  opacity: .08;
}
.glow-right {
  right: 10%;
  bottom: 18%;
  width: 340px;
  height: 340px;
  opacity: .05;
}
.auth-card {
  width: min(100%, 440px);
  padding: 38px;
  border: 1px solid var(--shop-border);
  border-radius: 16px;
  background: color-mix(in srgb, var(--shop-surface-lowest) 68%, transparent);
  box-shadow: 0 24px 70px rgba(0, 0, 0, .4);
  text-align: center;
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
}
.brand-mark {
  display: grid;
  width: 72px;
  height: 72px;
  margin: 0 auto 16px;
  place-items: center;
  border: 1px solid color-mix(in srgb, var(--shop-primary) 28%, transparent);
  border-radius: 50%;
  background: color-mix(in srgb, var(--shop-primary) 12%, var(--shop-surface-lowest));
  box-shadow: 0 0 28px color-mix(in srgb, var(--shop-primary) 15%, transparent);
  color: var(--shop-primary);
}
.brand-mark .material-symbols-outlined { font-size: 34px; }
.auth-card h1 {
  margin: 0;
  color: var(--shop-text);
  font-family: 'Sora', sans-serif;
  font-size: 26px;
  letter-spacing: -.03em;
}
.subtitle {
  margin: 8px 0 24px;
  color: var(--shop-text-muted);
  font-size: 14px;
}
.error-message {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 18px;
  padding: 11px 12px;
  border: 1px solid color-mix(in srgb, var(--shop-error) 30%, transparent);
  border-radius: 9px;
  background: color-mix(in srgb, var(--shop-error) 12%, transparent);
  color: var(--shop-error);
  font-size: 13px;
  text-align: left;
}
.error-message .material-symbols-outlined { font-size: 19px; }
.oauth-buttons { display: grid; gap: 10px; }
.oauth-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  width: 100%;
  min-height: 48px;
  padding: 11px 16px;
  border-radius: 9px;
  font: inherit;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: border-color .2s, background .2s, transform .2s;
}
.oauth-button:hover { transform: translateY(-1px); }
.google-button {
  border: 1px solid var(--shop-border);
  background: color-mix(in srgb, var(--shop-surface-low) 70%, transparent);
  color: var(--shop-text);
}
.google-button:hover { border-color: var(--shop-outline); background: var(--shop-surface-high); }
.line-button {
  border: 1px solid #06c755;
  background: #06c755;
  color: #fff;
  box-shadow: 0 10px 28px rgba(6, 199, 85, .16);
}
.line-button:hover { background: #05b34c; }
.line-button i { font-size: 20px; }
.divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 22px 0;
  color: var(--shop-text-muted);
  font-size: 11px;
  letter-spacing: .05em;
}
.divider::before,
.divider::after {
  flex: 1;
  height: 1px;
  background: var(--shop-border);
  content: '';
}
.form-group { margin-bottom: 16px; text-align: left; }
.form-group label {
  display: block;
  margin-bottom: 7px;
  color: var(--shop-text);
  font-size: 13px;
  font-weight: 700;
}
.form-group input {
  width: 100%;
  height: 48px;
  padding: 0 14px;
  border: 1px solid var(--shop-border);
  border-radius: 9px;
  outline: none;
  background: color-mix(in srgb, var(--shop-surface-low) 82%, transparent);
  color: var(--shop-text);
  font: inherit;
  font-size: 14px;
  transition: border-color .2s, box-shadow .2s;
}
.form-group input::placeholder { color: var(--shop-outline); }
.form-group input:focus {
  border-color: var(--shop-primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--shop-primary) 12%, transparent);
}
.password-field { position: relative; }
.password-field input { padding-right: 48px; }
.password-field button {
  position: absolute;
  top: 50%;
  right: 8px;
  display: grid;
  width: 36px;
  height: 36px;
  place-items: center;
  border: 0;
  background: transparent;
  color: var(--shop-text-muted);
  cursor: pointer;
  transform: translateY(-50%);
}
.password-field button:hover { color: var(--shop-primary); }
.password-field .material-symbols-outlined { font-size: 21px; }
.login-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  width: 100%;
  min-height: 48px;
  margin-top: 22px;
  border: 1px solid var(--shop-primary);
  border-radius: 9px;
  background: var(--shop-primary);
  box-shadow: 0 0 20px color-mix(in srgb, var(--shop-primary) 24%, transparent);
  color: var(--shop-on-primary);
  font: inherit;
  font-size: 14px;
  font-weight: 800;
  cursor: pointer;
  transition: background .2s, box-shadow .2s, transform .2s;
}
.login-button:hover:not(:disabled) {
  background: var(--shop-primary-strong);
  box-shadow: 0 0 28px color-mix(in srgb, var(--shop-primary) 38%, transparent);
  transform: translateY(-1px);
}
.login-button:disabled { cursor: wait; opacity: .65; }
.loader {
  width: 16px;
  height: 16px;
  border: 2px solid color-mix(in srgb, var(--shop-on-primary) 30%, transparent);
  border-top-color: var(--shop-on-primary);
  border-radius: 50%;
  animation: spin .8s linear infinite;
}
.register-link {
  margin: 24px 0 0;
  padding-top: 20px;
  border-top: 1px solid var(--shop-border);
  color: var(--shop-text-muted);
  font-size: 14px;
}
.register-link a {
  margin-left: 5px;
  color: var(--shop-primary);
  font-weight: 700;
  text-decoration: none;
}
.register-link a:hover { text-decoration: underline; }
.auth-footer {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 18px;
  padding: 22px 16px;
  border-top: 1px solid var(--shop-border);
  background: var(--shop-surface-lowest);
  color: var(--shop-text-muted);
  font-size: 10px;
  letter-spacing: .08em;
}
.auth-footer strong {
  color: var(--shop-text);
  font-family: 'Sora', sans-serif;
  font-size: 15px;
}
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 768px) {
  .auth-page { margin: -20px -16px; }
  .auth-stage { padding: 34px 16px; }
  .auth-card { padding: 28px 20px; }
  .glow-left { left: -220px; }
  .glow-right { right: -190px; }
}
@media (max-width: 420px) {
  .auth-footer { align-items: center; flex-direction: column; gap: 6px; }
}
</style>
