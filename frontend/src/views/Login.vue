<template>
  <AuthShell title="登入 SHOP" subtitle="歡迎回來，繼續您的購物之旅" footer>
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
            <span v-if="loading" class="loader loader-sm"></span>
            {{ loading ? '登入中...' : '立即登入' }}
          </button>
        </form>

    <p class="register-link">還沒有帳號？<router-link to="/register">立即註冊</router-link></p>
  </AuthShell>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { buildOAuthUrl } from '../api/oauth.js'
import { useSessionStore } from '../store/session.js'
import AuthShell from '../components/AuthShell.vue'

const route = useRoute()
const router = useRouter()
const session = useSessionStore()

const username = ref('001')
const password = ref('001')
const error = ref('')
const loading = ref(false)
const showPassword = ref(false)

function startOAuth(provider) {
  window.location.href = buildOAuthUrl(provider, route.query.redirect)
}

async function handleLogin() {
  loading.value = true
  error.value = ''
  const res = await session.login(username.value, password.value)
  if (res.success) {
    router.push(route.query.redirect || '/')
  } else {
    error.value = res.message
  }
  loading.value = false
}
</script>

<style scoped>
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
.form-group label {
  color: var(--shop-text);
  font-size: 13px;
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
</style>
