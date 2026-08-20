<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-user-cog"></i> 後台帳號</h1>
    </div>

    <div v-if="msg" :class="'msg msg-' + msgType">{{ msg }}</div>

    <div class="card">
      <h3 style="margin-bottom:16px;"><i class="fas fa-user-plus"></i> 新增後台帳號</h3>
      <form @submit.prevent="submit">
        <div class="form-group">
          <label>帳號 *</label>
          <input type="text" v-model="form.username" required placeholder="輸入使用者名稱" />
        </div>
        <div class="form-group">
          <label>密碼 *</label>
          <input type="text" v-model="form.password" required minlength="6" placeholder="至少 6 個字元" />
        </div>
        <div class="form-group">
          <label>權限（選填）</label>
          <input type="text" v-model="form.role" placeholder="例如 admin / manager / editor" />
        </div>
        <button class="btn btn-primary" style="width:100%;max-width:480px;" :disabled="loading">
          <i class="fas fa-save"></i> {{ loading ? '儲存中...' : '建立帳號' }}
        </button>
      </form>
    </div>

    <div class="card" style="padding:0;overflow:hidden;">
      <div v-if="loadingList" style="text-align:center;padding:48px;color:#888;"><i class="fas fa-spinner fa-spin"></i> 載入中...</div>
      <p v-else-if="!items.length" style="text-align:center;padding:48px;color:#888;">尚無後台帳號</p>
      <table v-else>
        <thead>
          <tr>
            <th>ID</th>
            <th>帳號</th>
            <th style="text-align:center;">權限</th>
            <th style="text-align:center;">建立時間</th>
            <th style="text-align:center;width:150px;">操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in items" :key="a.id">
            <td style="color:#888;">{{ a.id }}</td>
            <td style="font-weight:600;">
              {{ a.username }}
              <span v-if="a.id === selfId" class="tag">目前登入</span>
            </td>
            <td style="text-align:center;">
              <span v-if="a.role" class="tag role">{{ a.role }}</span>
              <span v-else style="color:#bbb;">—</span>
            </td>
            <td style="text-align:center;color:#888;font-size:13px;">{{ formatDate(a.created_at) }}</td>
            <td style="text-align:center;">
              <button class="icon-btn danger" title="刪除" :disabled="a.id === selfId" @click="remove(a)">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'

export default {
  name: 'AccountsView',
  data() {
    return {
      items: [],
      selfId: 0,
      form: { username: '', password: '', role: '' },
      loading: false,
      loadingList: true,
      msg: '',
      msgType: 'success',
    }
  },
  created() {
    this.load()
  },
  methods: {
    formatDate(s) {
      if (!s) return '—'
      return String(s).replace('T', ' ').slice(0, 16)
    },
    async load() {
      this.loadingList = true
      const res = await api.accounts()
      if (res.success) {
        this.items = res.data.items || []
        this.selfId = res.data.self_id || 0
      }
      this.loadingList = false
    },
    async submit() {
      this.loading = true
      this.msg = ''
      const res = await api.createAccount(this.form)
      this.loading = false
      this.msgType = res.success ? 'success' : 'error'
      this.msg = res.message
      if (res.success) {
        this.form = { username: '', password: '', role: '' }
        this.load()
      }
    },
    async remove(a) {
      if (!confirm(`確定要刪除後台帳號「${a.username}」嗎？`)) return
      const res = await api.deleteAccount(a.id)
      this.msgType = res.success ? 'success' : 'error'
      this.msg = res.message
      if (res.success) this.load()
    },
  },
}
</script>

<style scoped>
.tag { background: #e3f2fd; padding: 2px 10px; border-radius: 10px; font-size: 12px; color: #1565c0; margin-left: 6px; display: inline-block; }
.tag.role { background: #ede7f6; color: #6a1b9a; margin-left: 0; }
.icon-btn { background: none; border: none; cursor: pointer; color: #4CAF50; font-size: 16px; padding: 6px 8px; border-radius: 6px; transition: background 0.2s; }
.icon-btn:hover:not(:disabled) { background: #f0f2f5; }
.icon-btn.danger { color: #f44336; }
.icon-btn:disabled { opacity: 0.35; cursor: not-allowed; }
</style>