<template>
  <section class="register-page">
    <div class="ambient ambient-top"></div>
    <div class="ambient ambient-bottom"></div>

    <div class="register-card">
      <div class="brand-mark">
        <span class="material-symbols-outlined">storefront</span>
      </div>
      <h1>建立帳號</h1>
      <p class="subtitle">加入 SHOP，體驗高科技零售新境界</p>

      <div v-if="error" class="message error-message" role="alert">
        <span class="material-symbols-outlined">error</span>
        {{ error }}
      </div>
      <div v-if="success" class="message success-message" role="status">
        <span class="material-symbols-outlined">check_circle</span>
        <span>{{ success }}，<router-link to="/login">前往登入</router-link></span>
      </div>

      <form @submit.prevent="handleRegister">
        <div class="form-group">
          <label for="register-username">帳號</label>
          <div class="input-field">
            <span class="material-symbols-outlined">person</span>
            <input id="register-username" v-model.trim="username" type="text" autocomplete="username" required placeholder="請輸入使用者名稱" />
          </div>
        </div>
        <div class="form-group">
          <label for="register-email">電子郵件</label>
          <div class="input-field">
            <span class="material-symbols-outlined">mail</span>
            <input id="register-email" v-model.trim="email" type="email" autocomplete="email" required placeholder="name@example.com" />
          </div>
        </div>
        <div class="form-group">
          <label for="register-password">密碼</label>
          <div class="input-field password-field">
            <span class="material-symbols-outlined">lock</span>
            <input id="register-password" v-model="password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" minlength="6" required placeholder="至少 6 個字元" />
            <button type="button" :aria-label="showPassword ? '隱藏密碼' : '顯示密碼'" @click="showPassword = !showPassword">
              <span class="material-symbols-outlined">{{ showPassword ? 'visibility_off' : 'visibility' }}</span>
            </button>
          </div>
        </div>
        <div class="form-group">
          <label for="register-confirm">確認密碼</label>
          <div class="input-field">
            <span class="material-symbols-outlined">lock_reset</span>
            <input id="register-confirm" v-model="confirmPassword" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" minlength="6" required placeholder="再次輸入密碼" />
          </div>
        </div>
        <button class="register-button" type="submit" :disabled="loading">
          <span v-if="loading" class="loader"></span>
          {{ loading ? '註冊中...' : '註冊' }}
        </button>
      </form>

      <p class="login-link">已經有帳號了？<router-link to="/login">登入</router-link></p>
    </div>
  </section>
</template>

<script>
import { api } from '../api/index.js'
export default {
  data() {
    return { username: '', email: '', password: '', confirmPassword: '', error: '', success: '', loading: false, showPassword: false }
  },
  methods: {
    async handleRegister() {
      this.error = ''
      this.success = ''
      if (this.password !== this.confirmPassword) {
        this.error = '兩次輸入的密碼不一致'
        return
      }
      this.loading = true
      const res = await api.register(this.username, this.email, this.password)
      if (res.success) {
        this.success = '註冊成功'
        this.password = ''
        this.confirmPassword = ''
      } else {
        this.error = res.message
      }
      this.loading = false
    },
  },
}
</script>

<style scoped>
.register-page {
  position: relative;
  display: flex;
  min-height: 100vh;
  margin: -30px -40px;
  padding: 56px 16px;
  align-items: center;
  justify-content: center;
  background: var(--shop-background);
  overflow: hidden;
}
.ambient {
  position: absolute;
  border-radius: 50%;
  background: var(--shop-primary-strong);
  filter: blur(110px);
  pointer-events: none;
}
.ambient-top {
  top: -140px;
  right: -80px;
  width: 430px;
  height: 430px;
  opacity: .1;
}
.ambient-bottom {
  bottom: -160px;
  left: -100px;
  width: 380px;
  height: 380px;
  opacity: .06;
}
.register-card {
  position: relative;
  z-index: 1;
  width: min(100%, 460px);
  padding: 38px;
  border: 1px solid var(--shop-border);
  border-radius: 16px;
  background: color-mix(in srgb, var(--shop-background) 65%, transparent);
  box-shadow: 0 24px 70px rgba(0, 0, 0, .42);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
}
.brand-mark {
  display: grid;
  width: 64px;
  height: 64px;
  margin: 0 auto 16px;
  place-items: center;
  border: 1px solid color-mix(in srgb, var(--shop-primary) 28%, transparent);
  border-radius: 13px;
  background: var(--shop-surface);
  box-shadow: 0 0 26px color-mix(in srgb, var(--shop-primary) 13%, transparent);
  color: var(--shop-primary);
}
.brand-mark .material-symbols-outlined { font-size: 32px; }
.register-card h1 {
  margin: 0;
  color: var(--shop-primary);
  font-family: 'Sora', sans-serif;
  font-size: clamp(28px, 5vw, 34px);
  letter-spacing: -.04em;
  text-align: center;
}
.subtitle {
  margin: 8px 0 26px;
  color: var(--shop-text-muted);
  font-size: 14px;
  text-align: center;
}
.message {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 18px;
  padding: 11px 12px;
  border-radius: 9px;
  font-size: 13px;
}
.message .material-symbols-outlined { font-size: 19px; }
.error-message {
  border: 1px solid color-mix(in srgb, var(--shop-error) 30%, transparent);
  background: color-mix(in srgb, var(--shop-error) 12%, transparent);
  color: var(--shop-error);
}
.success-message {
  border: 1px solid color-mix(in srgb, var(--shop-primary) 30%, transparent);
  background: color-mix(in srgb, var(--shop-primary) 10%, transparent);
  color: var(--shop-primary);
}
.success-message a { color: var(--shop-primary); font-weight: 700; }
.form-group { margin-bottom: 16px; }
.form-group label {
  display: block;
  margin-bottom: 7px;
  color: var(--shop-text-muted);
  font-size: 12px;
  font-weight: 700;
}
.input-field { position: relative; }
.input-field > .material-symbols-outlined {
  position: absolute;
  top: 50%;
  left: 12px;
  color: var(--shop-text-muted);
  font-size: 20px;
  pointer-events: none;
  transform: translateY(-50%);
}
.input-field input {
  width: 100%;
  height: 48px;
  padding: 0 14px 0 42px;
  border: 1px solid color-mix(in srgb, var(--shop-outline-variant, var(--shop-border)) 55%, transparent);
  border-radius: 9px;
  outline: none;
  background: color-mix(in srgb, var(--shop-surface-low) 72%, transparent);
  color: var(--shop-text);
  font: inherit;
  font-size: 14px;
  transition: border-color .2s, box-shadow .2s;
}
.input-field input::placeholder { color: var(--shop-outline); }
.input-field input:focus {
  border-color: var(--shop-primary);
  box-shadow: inset 0 0 15px color-mix(in srgb, var(--shop-primary) 12%, transparent);
}
.password-field input { padding-right: 48px; }
.password-field button {
  position: absolute;
  top: 50%;
  right: 7px;
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
.password-field button .material-symbols-outlined { font-size: 21px; }
.register-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  width: 100%;
  min-height: 50px;
  margin-top: 24px;
  border: 1px solid var(--shop-primary-strong);
  border-radius: 9px;
  background: linear-gradient(135deg, var(--shop-primary-strong), var(--shop-on-primary-container, var(--shop-on-primary)));
  color: var(--shop-text);
  font: inherit;
  font-size: 15px;
  font-weight: 800;
  cursor: pointer;
  transition: box-shadow .2s, transform .2s;
}
.register-button:hover:not(:disabled) {
  box-shadow: 0 0 24px color-mix(in srgb, var(--shop-primary) 38%, transparent);
  transform: scale(1.01);
}
.register-button:disabled { cursor: wait; opacity: .65; }
.loader {
  width: 16px;
  height: 16px;
  border: 2px solid color-mix(in srgb, var(--shop-text) 30%, transparent);
  border-top-color: var(--shop-text);
  border-radius: 50%;
  animation: spin .8s linear infinite;
}
.login-link {
  margin: 26px 0 0;
  color: var(--shop-text-muted);
  font-size: 14px;
  text-align: center;
}
.login-link a {
  margin-left: 5px;
  color: var(--shop-primary);
  font-weight: 700;
  text-decoration: none;
}
.login-link a:hover { text-decoration: underline; }
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 768px) {
  .register-page { margin: -20px -16px; padding: 34px 16px; }
  .register-card { padding: 28px 20px; }
}
</style>
