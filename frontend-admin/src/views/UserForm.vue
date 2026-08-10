<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-user-plus"></i> 新增會員</h1>
      <router-link to="/users" class="btn btn-default"><i class="fas fa-arrow-left"></i> 返回列表</router-link>
    </div>

    <div v-if="msg" :class="'msg msg-' + msgType">{{ msg }}</div>

    <div class="card" style="max-width:480px;">
      <form @submit.prevent="submit">
        <div class="form-group">
          <label>會員名稱 *</label>
          <input type="text" v-model="form.username" required placeholder="輸入使用者名稱" />
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input type="email" v-model="form.email" required placeholder="輸入 Email" />
        </div>
        <div class="form-group">
          <label>密碼 *</label>
          <input type="password" v-model="form.password" required minlength="6" placeholder="至少 6 個字元" />
        </div>
        <button class="btn btn-primary" style="width:100%;" :disabled="loading">
          <i class="fas fa-save"></i> {{ loading ? '儲存中...' : '建立會員' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'

export default {
  name: 'UserFormView',
  data() {
    return { form: { username: '', email: '', password: '' }, msg: '', msgType: 'success', loading: false }
  },
  methods: {
    async submit() {
      this.loading = true
      this.msg = ''
      const res = await api.createUser(this.form)
      this.loading = false
      this.msgType = res.success ? 'success' : 'error'
      this.msg = res.message
      if (res.success) {
        this.form = { username: '', email: '', password: '' }
      }
    },
  },
}
</script>
