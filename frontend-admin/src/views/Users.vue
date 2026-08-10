<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-users"></i> 會員列表</h1>
      <router-link to="/users/add" class="btn btn-primary"><i class="fas fa-plus"></i> 新增會員</router-link>
    </div>

    <div class="card filter-bar">
      <input type="text" v-model="q" placeholder="搜尋會員名稱或 Email..." @keyup.enter="search" />
      <button class="btn btn-primary" @click="search"><i class="fas fa-search"></i> 搜尋</button>
      <button v-if="q" class="btn btn-default" @click="reset">清除</button>
    </div>

    <div v-if="msg" :class="'msg msg-' + msgType">{{ msg }}</div>

    <div class="card" style="padding:0;overflow:hidden;">
      <div v-if="loading" style="text-align:center;padding:48px;color:#888;"><i class="fas fa-spinner fa-spin"></i> 載入中...</div>
      <p v-else-if="!items.length" style="text-align:center;padding:48px;color:#888;">尚無會員</p>
      <table v-else>
        <thead>
          <tr>
            <th>ID</th>
            <th>會員名稱</th>
            <th>Email</th>
            <th style="text-align:center;">來源</th>
            <th style="text-align:center;">電話</th>
            <th style="text-align:center;">加入時間</th>
            <th style="text-align:center;width:130px;">操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in items" :key="u.id">
            <td style="color:#888;">{{ u.id }}</td>
            <td style="font-weight:600;">
              <img v-if="u.avatar" :src="u.avatar" style="width:28px;height:28px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:8px;" />
              <i v-else class="fas fa-user-circle" style="font-size:28px;vertical-align:middle;margin-right:8px;color:#ccc;"></i>
              {{ u.username }}
            </td>
            <td>{{ u.email }}</td>
            <td style="text-align:center;">
              <span v-if="u.provider" class="tag">{{ u.provider }}</span>
              <span v-else style="color:#bbb;">本機</span>
            </td>
            <td style="text-align:center;color:#888;">{{ u.phone || '—' }}</td>
            <td style="text-align:center;color:#888;font-size:13px;">{{ formatDate(u.created_at) }}</td>
            <td style="text-align:center;">
              <button class="icon-btn" title="重設密碼" @click="openReset(u)"><i class="fas fa-key"></i></button>
              <button class="icon-btn danger" title="刪除" @click="remove(u)"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showReset" class="modal-mask" @click.self="showReset = false">
      <div class="modal">
        <h3><i class="fas fa-key"></i> 重設密碼</h3>
        <p style="color:#888;font-size:13px;margin:6px 0 14px;">會員：<b style="color:#333;">{{ resetTarget.username }}</b></p>
        <div class="form-group">
          <label>新密碼</label>
          <input type="password" v-model="newPassword" required placeholder="輸入新密碼" />
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button class="btn btn-default" @click="showReset = false">取消</button>
          <button class="btn btn-primary" :disabled="saving" @click="resetPassword">確定</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'

export default {
  name: 'UsersView',
  data() {
    return { items: [], q: '', loading: true, msg: '', msgType: 'success', showReset: false, resetTarget: null, newPassword: '', saving: false }
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
      this.loading = true
      const res = await api.users(this.q)
      if (res.success) this.items = res.data || []
      this.loading = false
    },
    search() {
      this.load()
    },
    reset() {
      this.q = ''
      this.load()
    },
    openReset(u) {
      if (u.provider) {
        this.msgType = 'error'
        this.msg = '三方登入會員無密碼可修改'
        return
      }
      this.resetTarget = u
      this.newPassword = ''
      this.showReset = true
    },
    async resetPassword() {
      this.saving = true
      const res = await api.updateUserPassword(this.resetTarget.id, this.newPassword)
      this.saving = false
      if (res.success) {
        this.showReset = false
        this.msgType = 'success'
        this.msg = res.message
      } else {
        this.msgType = 'error'
        this.msg = res.message
      }
    },
    async remove(u) {
      if (!confirm(`確定要刪除會員「${u.username}」嗎？`)) return
      const res = await api.deleteUser(u.id)
      this.msgType = res.success ? 'success' : 'error'
      this.msg = res.message
      if (res.success) this.load()
    },
  },
}
</script>

<style scoped>
.filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; padding: 16px 24px; }
.filter-bar input {
  padding: 10px 14px; border: 1px solid #d0d5dd; border-radius: 8px; font-size: 14px; outline: none; min-width: 240px; flex: 1;
}
.filter-bar input:focus { border-color: #4CAF50; box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.12); }
.tag { background: #e3f2fd; padding: 2px 10px; border-radius: 10px; font-size: 12px; color: #1565c0; }
.icon-btn { background: none; border: none; cursor: pointer; color: #4CAF50; font-size: 16px; padding: 6px 8px; border-radius: 6px; transition: background 0.2s; }
.icon-btn:hover { background: #f0f2f5; }
.icon-btn.danger { color: #f44336; }
.modal-mask {
  position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.modal {
  background: #fff; border-radius: 12px; padding: 28px; width: 380px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
}
.modal h3 { font-size: 17px; margin-bottom: 8px; }
.modal input { width: 100%; max-width: 100%; }
</style>
