<template>
  <div style="text-align:center;padding:60px;color:#888;">
    <i class="fas fa-spinner fa-spin" style="font-size:32px;margin-bottom:16px;"></i>
    <p>三方登入處理中...</p>
  </div>
</template>

<script>
import { api } from '../api/index.js'
import { userStore } from '../store/user.js'
import { toastStore } from '../store/toast.js'
import { verifyOAuthState, extractOAuthRedirect } from '../api/oauth.js'

export default {
  async created() {
    const code = this.$route.query.code
    const state = this.$route.query.state
    const redirect = extractOAuthRedirect(state) || this.$route.query.redirect || '/'
    if (code && state && verifyOAuthState(state)) {
      const provider = state.split('-')[0]
      const res = await api.oauthLogin(provider, code)
      if (res.success) {
        userStore.set(res.data.user)
        this.$router.push(redirect)
        return
      }
      toastStore.error(res.message)
    }
    this.$router.push('/login')
  },
}
</script>
