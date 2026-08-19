<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-user-shield"></i> 帳號設定</h1>
    </div>

    <div v-if="msg" :class="'msg msg-' + msgType">{{ msg }}</div>

    <div class="card" style="max-width:640px;">
      <div class="account-info">
        <div class="account-row"><span>使用者名稱</span><strong>{{ authStore.admin?.username }}</strong></div>
      </div>
    </div>

    <div class="card" style="max-width:640px;">
      <h3 style="margin-bottom:6px;"><i class="fas fa-link"></i> 三方登入綁定</h3>
      <p style="color:#888;font-size:13px;margin-bottom:18px;">
        綁定後即可用 LINE / Google 登入後台；同一三方帳號只能綁定一個後台帳號。
      </p>
      <div class="provider-row" v-for="p in providers" :key="p.key">
        <div class="provider-icon" :class="p.key">
          <i :class="p.icon"></i>
        </div>
        <div class="provider-copy">
          <div class="provider-name">{{ p.label }}</div>
          <div class="provider-state" v-if="bound(p.key)">
            已綁定
          </div>
          <div class="provider-state" v-else>尚未綁定</div>
        </div>
        <button
          v-if="bound(p.key)"
          class="btn btn-danger"
          @click="unbind(p.key)"
        >
          <i class="fas fa-unlink"></i> 解除綁定
        </button>
        <button v-else class="btn btn-primary" @click="bind(p.key)">
          <i class="fas fa-link"></i> 綁定
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
  name: 'AccountView',
  data() {
    return {
      providers: [
        { key: 'google', label: 'Google', icon: 'fab fa-google' },
        { key: 'line', label: 'LINE', icon: 'fab fa-line' },
      ],
      msg: '',
      msgType: 'success',
      authStore,
    }
  },
  methods: {
    bound(key) {
      return authStore.admin?.provider === key
    },
    bind(key) {
      window.location.href = buildOAuthUrl(key, 'bind')
    },
    async unbind(key) {
      const res = await api.oauthUnbind(key)
      this.msgType = res.success ? 'success' : 'error'
      this.msg = res.message
      if (res.success) await authStore.fetch()
    },
  },
}
</script>

<style scoped>
.account-info { display: grid; gap: 12px; }
.account-row {
  display: flex; justify-content: space-between; align-items: center; gap: 12px;
  padding: 12px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px;
}
.account-row:last-child { border-bottom: none; }
.account-row span { color: #888; }
.provider-row {
  display: flex; align-items: center; gap: 14px; padding: 14px 0;
  border-bottom: 1px solid #f0f0f0;
}
.provider-row:last-child { border-bottom: none; }
.provider-icon {
  width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center;
  justify-content: center; font-size: 20px; color: #fff; flex: 0 0 42px;
}
.provider-icon.google { background: #4285F4; }
.provider-icon.line { background: #06c755; }
.provider-copy { flex: 1; }
.provider-name { font-weight: 600; font-size: 15px; }
.provider-state { font-size: 12px; color: #888; margin-top: 2px; }
</style>