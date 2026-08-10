<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-scroll"></i> 跑馬燈</h1>
    </div>

    <div v-if="msg" :class="'msg msg-' + msgType">{{ msg }}</div>

    <div class="card" style="max-width:640px;">
      <form @submit.prevent="submit">
        <div class="form-group">
          <label>跑馬燈內容</label>
          <textarea v-model="content" placeholder="輸入要顯示的跑馬燈文字" style="min-height:80px;"></textarea>
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
  name: 'MarqueeView',
  data() {
    return { content: '', msg: '', msgType: 'success', loading: false }
  },
  async created() {
    const res = await api.marquee()
    if (res.success && res.data) this.content = res.data.content || ''
  },
  methods: {
    async submit() {
      this.loading = true
      this.msg = ''
      const res = await api.updateMarquee(this.content)
      this.loading = false
      this.msgType = res.success ? 'success' : 'error'
      this.msg = res.message
    },
  },
}
</script>
