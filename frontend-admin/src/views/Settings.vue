<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-cog"></i> 系統設定</h1>
    </div>

    <div v-if="msg" :class="'msg msg-' + msgType">{{ msg }}</div>

    <div class="card" style="max-width:640px;">
      <h3><i class="fas fa-chair"></i> 桌數設定</h3>
      <form @submit.prevent="submitTable">
        <div class="form-group">
          <label>總桌數</label>
          <input type="number" v-model.number="tableCount" min="0" max="200" />
          <small style="color:#999;">點單時可選擇 1 ~ {{ tableCount }} 號桌；設為 0 表示不提供桌號。</small>
        </div>
        <button class="btn btn-primary" :disabled="loadingTable">
          <i class="fas fa-save"></i> {{ loadingTable ? '儲存中...' : '儲存' }}
        </button>
      </form>
    </div>

    <div class="card" style="max-width:640px;">
      <h3><i class="fas fa-th-list"></i> 菜單顯示方式</h3>
      <form @submit.prevent="submitMenuLayout">
        <div class="form-group">
          <label>前台點餐菜單的呈現樣式</label>
          <div class="layout-options">
            <label class="layout-option">
              <input type="radio" value="grid" v-model="menuLayout" />
              <span><i class="fas fa-th"></i> 卡片式</span>
              <small>直接顯示圖片、說明與加入按鈕</small>
            </label>
            <label class="layout-option">
              <input type="radio" value="list" v-model="menuLayout" />
              <span><i class="fas fa-list"></i> 條列式</span>
              <small>僅顯示名稱與價格，點擊看詳細</small>
            </label>
          </div>
        </div>
        <button class="btn btn-primary" :disabled="loadingMenuLayout">
          <i class="fas fa-save"></i> {{ loadingMenuLayout ? '儲存中...' : '儲存' }}
        </button>
      </form>
    </div>

    <div class="card" style="max-width:640px;">
      <h3><i class="fas fa-credit-card"></i> 三方支付設定（LINE Pay）</h3>
      <form @submit.prevent="submitLinePay">
        <div class="form-group">
          <label>Channel ID</label>
          <input type="text" v-model="linepay.channel_id" placeholder="1234567890" />
        </div>
        <div class="form-group">
          <label>Channel Secret</label>
          <input type="password" v-model="linepay.channel_secret" placeholder="••••••••••••••••" autocomplete="off" />
        </div>
        <div class="form-group checkbox-row">
          <label class="check-label"><input type="checkbox" v-model="linepay.sandbox" /> 沙盒模式（測試用，不會真的收款）</label>
        </div>
        <small style="display:block;color:#999;margin-bottom:14px;">未填寫 Channel ID / Secret 即停用 LINE Pay 付款。</small>
        <button class="btn btn-primary" :disabled="loadingLinePay">
          <i class="fas fa-save"></i> {{ loadingLinePay ? '儲存中...' : '儲存' }}
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
    return {
      tableCount: 0,
      menuLayout: 'grid',
      linepay: { channel_id: '', channel_secret: '', sandbox: false },
      msg: '',
      msgType: 'success',
      loadingTable: false,
      loadingMenuLayout: false,
      loadingLinePay: false,
    }
  },
  async created() {
    const res = await api.settings()
    if (res.success && res.data) {
      this.tableCount = res.data.table_count || 0
      this.menuLayout = res.data.menu_layout === 'list' ? 'list' : 'grid'
      this.linepay = {
        channel_id: res.data.linepay?.channel_id || '',
        channel_secret: res.data.linepay?.channel_secret || '',
        sandbox: res.data.linepay?.sandbox === '1',
      }
    }
  },
  methods: {
    notify(res) {
      this.msgType = res.success ? 'success' : 'error'
      this.msg = res.message
    },
    async submitTable() {
      this.loadingTable = true
      this.msg = ''
      const res = await api.updateTableCount(this.tableCount)
      this.loadingTable = false
      this.notify(res)
    },
    async submitLinePay() {
      this.loadingLinePay = true
      this.msg = ''
      const res = await api.updateLinePay({
        channel_id: this.linepay.channel_id.trim(),
        channel_secret: this.linepay.channel_secret.trim(),
        sandbox: this.linepay.sandbox ? '1' : '0',
      })
      this.loadingLinePay = false
      this.notify(res)
    },
    async submitMenuLayout() {
      this.loadingMenuLayout = true
      this.msg = ''
      const res = await api.updateMenuLayout(this.menuLayout)
      this.loadingMenuLayout = false
      this.notify(res)
    },
  },
}
</script>

<style scoped>
.card h3 { font-size: 15px; margin-bottom: 16px; color: #333; }
.checkbox-row .check-label { display: flex; align-items: center; gap: 8px; font-weight: 400; color: #444; font-size: 14px; cursor: pointer; }
.checkbox-row input[type="checkbox"] { width: 16px; height: 16px; }
.layout-options { display: flex; flex-direction: column; gap: 10px; }
.layout-option {
  display: flex; align-items: flex-start; gap: 10px; padding: 12px;
  border: 1px solid #ddd; border-radius: 8px; cursor: pointer;
  font-size: 14px; color: #333;
}
.layout-option:has(input:checked) { border-color: #4a6cf7; background: #f0f3ff; }
.layout-option input[type="radio"] { margin-top: 2px; }
.layout-option small { display: block; margin-left: 26px; color: #999; font-size: 12px; }
</style>