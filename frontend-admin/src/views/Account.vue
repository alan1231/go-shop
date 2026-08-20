<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-user-shield"></i> 帳號設定</h1>
    </div>

    <div class="card" style="max-width:640px;">
      <div class="account-info">
        <div class="account-row"><span>使用者名稱</span><strong>{{ authStore.admin?.username }}</strong></div>
        <div class="account-row"><span>權限</span><strong>{{ authStore.admin?.role || '—' }}</strong></div>
      </div>
    </div>
  </div>
</template>

<script>
import { authStore } from '../store/auth.js'

export default {
  name: 'AccountView',
  data() {
    return {
      authStore,
    }
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
</style>