<template>
  <div style="text-align:center;padding:60px;color:#888;">
    <i class="fas fa-spinner fa-spin" style="font-size:32px;margin-bottom:16px;"></i>
    <p>三方登入處理中...</p>
  </div>
</template>

<script>
import { api } from '../api/index.js'
import { userStore } from '../store/user.js'

export default {
  async created() {
    const code = this.$route.query.code
    const state = this.$route.query.state
    if (code && state) {
      const res = await api.oauthLogin(state, code)
      if (res.success) {
        userStore.set(res.data.user)
        this.$router.push('/')
        return
      }
      alert(res.message)
    }
    this.$router.push('/login')
  },
}
</script>
