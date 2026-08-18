<template>
  <div style="text-align:center;padding:60px;color:#888;">
    <i class="fas fa-spinner fa-spin" style="font-size:32px;margin-bottom:16px;"></i>
    <p>三方登入處理中...</p>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSessionStore } from '../store/session.js'
import { useToastStore } from '../store/toast.js'
import { verifyOAuthState, extractOAuthRedirect } from '../api/oauth.js'

const route = useRoute()
const router = useRouter()
const session = useSessionStore()
const toastStore = useToastStore()

onMounted(async () => {
  const code = route.query.code
  const state = route.query.state
  const redirect = extractOAuthRedirect(state) || route.query.redirect || '/'
  if (code && state && verifyOAuthState(state)) {
    const provider = state.split('-')[0]
    const res = await session.completeOAuth(provider, code)
    if (res.success) {
      router.push(redirect)
      return
    }
    toastStore.error(res.message)
  }
  router.push('/login')
})
</script>
