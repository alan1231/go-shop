<template>
  <AuthShell title="建立帳號" subtitle="加入 SHOP，體驗高科技零售新境界" variant="register">
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
          <span v-if="loading" class="loader loader-sm"></span>
          {{ loading ? '註冊中...' : '註冊' }}
        </button>
      </form>

    <p class="login-link">已經有帳號了？<router-link to="/login">登入</router-link></p>
  </AuthShell>
</template>

<script>
import { api } from '../api/index.js'
import AuthShell from '../components/AuthShell.vue'
export default {
  components: { AuthShell },
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
</style>
