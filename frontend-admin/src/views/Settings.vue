<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-chair"></i> 桌數設定</h1>
    </div>

    <div v-if="msg" :class="'msg msg-' + msgType">{{ msg }}</div>

    <div class="card" style="max-width:640px;">
      <form @submit.prevent="submit">
        <div class="form-group">
          <label>總桌數</label>
          <input type="number" v-model.number="tableCount" min="0" max="200" />
          <small style="color:#999;">點單時可選擇 1 ~ {{ tableCount }} 號桌；設為 0 表示不提供桌號。</small>
        </div>
        <button class="btn btn-primary" :disabled="loading">
          <i class="fas fa-save"></i> {{ loading ? '儲存中...' : '儲存' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'

export default {
  name: 'SettingsView',
  data() {
    return { tableCount: 0, msg: '', msgType: 'success', loading: false }
  },
  async created() {
    const res = await api.settings()
    if (res.success && res.data) this.tableCount = res.data.table_count || 0
  },
  methods: {
    async submit() {
      this.loading = true
      this.msg = ''
      const res = await api.updateTableCount(this.tableCount)
      this.loading = false
      this.msgType = res.success ? 'success' : 'error'
      this.msg = res.message
    },
  },
}
</script>
