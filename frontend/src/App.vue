<template>
  <div id="layout" :class="{ 'page-home': $route.name === 'home' }">
    <nav v-if="showHeader">
      <div class="inner">
        <router-link to="/" class="logo"><i class="fas fa-store"></i> SHOP</router-link>
        <button class="nav-toggle" @click.stop="mobileOpen = !mobileOpen" aria-label="選單">
          <i class="fas" :class="mobileOpen ? 'fa-times' : 'fa-bars'"></i>
        </button>
        <div class="nav-links" :class="{ open: mobileOpen }">
          <router-link to="/"><i class="fas fa-home"></i> 首頁</router-link>
          <router-link to="/cart"><i class="fas fa-shopping-cart"></i> 購物車 ({{ cartCount }})</router-link>
          <router-link v-if="user" to="/orders"><i class="fas fa-file-invoice"></i> 訂單</router-link>
          <span v-if="user" class="user-info" @click.stop="toggleUserMenu">
            <i class="fas fa-user-circle"></i> {{ user.username }}
            <i class="fas fa-caret-down" style="font-size:11px;"></i>
          </span>
          <div v-if="userMenuOpen" class="user-card" @click.stop>
            <div class="uc-header">
              <img v-if="user.avatar" :src="user.avatar" class="uc-avatar" alt="avatar" @error="avatarError" />
              <div v-else class="uc-avatar">{{ user.username.charAt(0).toUpperCase() }}</div>
              <div class="uc-meta">
                <div class="uc-name">{{ user.username }}</div>
                <div class="uc-provider" :class="'p-' + (user.provider || 'local')">
                  <i :class="providerIcon"></i> {{ providerLabel }}
                </div>
              </div>
            </div>
            <div class="uc-body">
              <div class="uc-row"><i class="fas fa-envelope"></i><span>{{ user.email || '未提供' }}</span></div>
              <template v-if="!editing">
                <div class="uc-row"><i class="fas fa-phone"></i><span>{{ user.phone || '未提供' }}</span></div>
                <div class="uc-row"><i class="fas fa-map-marker-alt"></i><span>{{ user.address || '未提供' }}</span></div>
              </template>
              <template v-else>
                <div class="uc-row uc-edit"><i class="fas fa-phone"></i><input v-model="editPhone" type="text" placeholder="手機號碼" /></div>
                <div class="uc-row uc-edit"><i class="fas fa-map-marker-alt"></i><input v-model="editAddress" type="text" placeholder="住址" /></div>
              </template>
              <div class="uc-row"><i class="fas fa-calendar-alt"></i><span>加入於 {{ memberSince }}</span></div>
            </div>
            <div class="uc-footer">
              <template v-if="!editing">
                <button class="btn btn-primary btn-sm" @click="startEdit"><i class="fas fa-edit"></i> 編輯資料</button>
              </template>
              <template v-else>
                <button class="btn btn-primary btn-sm" @click="saveEdit" :disabled="saving">{{ saving ? '儲存中...' : '儲存' }}</button>
                <button class="btn btn-default btn-sm" @click="cancelEdit">取消</button>
              </template>
              <button v-if="!editing" class="btn btn-default btn-sm" @click="togglePwEdit"><i class="fas fa-key"></i> 更改密碼</button>
              <button v-if="!editing" class="btn btn-danger btn-sm" @click="handleLogout"><i class="fas fa-sign-out-alt"></i> 登出</button>
            </div>
            <div v-if="pwEdit" class="uc-pw">
              <div class="uc-row uc-edit"><i class="fas fa-lock"></i><input v-model="pwOld" type="password" placeholder="原密碼（OAuth 會員可留空）" /></div>
              <div class="uc-row uc-edit"><i class="fas fa-lock"></i><input v-model="pwNew" type="password" placeholder="新密碼（至少 6 碼）" /></div>
              <div class="uc-row uc-edit"><i class="fas fa-lock"></i><input v-model="pwConfirm" type="password" placeholder="確認新密碼" /></div>
              <div class="uc-pw-actions">
                <button class="btn btn-primary btn-sm" @click="savePassword" :disabled="pwSaving">{{ pwSaving ? '儲存中...' : '確認更改' }}</button>
                <button class="btn btn-default btn-sm" style="margin-left:8px;" @click="togglePwEdit">取消</button>
              </div>
            </div>
          </div>
          <router-link v-if="!user" to="/login"><i class="fas fa-sign-in-alt"></i> 登入</router-link>
          <a v-else href="#" @click.prevent="handleLogout"><i class="fas fa-sign-out-alt"></i> 登出</a>
        </div>
      </div>
    </nav>
    <div class="site-marquee" v-if="showHeader && site.marqueeText">
      <span>{{ site.marqueeText }}</span>
    </div>
    <main>
      <router-view />
    </main>
    <footer v-if="showHeader">
      <p>&copy; 2026 SHOP</p>
    </footer>
    <Toast />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSessionStore } from './store/session.js'
import { useSiteStore } from './store/site.js'
import { useCartStore } from './store/cart.js'
import { useToastStore } from './store/toast.js'
import Toast from './components/Toast.vue'

const route = useRoute()
const router = useRouter()
const session = useSessionStore()
const site = useSiteStore()
const cartStore = useCartStore()
const toastStore = useToastStore()

const userMenuOpen = ref(false)
const mobileOpen = ref(false)
const editing = ref(false)
const saving = ref(false)
const editPhone = ref('')
const editAddress = ref('')
const pwEdit = ref(false)
const pwSaving = ref(false)
const pwOld = ref('')
const pwNew = ref('')
const pwConfirm = ref('')

const showHeader = computed(() => !['login', 'register', 'oauth-callback'].includes(route.name))
const cartCount = computed(() => cartStore.count)
const user = computed(() => session.user)
const providerLabel = computed(() => {
  if (!user.value) return ''
  if (user.value.provider === 'google') return 'Google 登入'
  if (user.value.provider === 'line') return 'LINE 登入'
  return '帳號登入'
})
const providerIcon = computed(() => {
  if (!user.value) return ''
  if (user.value.provider === 'google') return 'fab fa-google'
  if (user.value.provider === 'line') return 'fab fa-line'
  return 'fas fa-user'
})
const memberSince = computed(() => {
  if (!user.value || !user.value.created_at) return ''
  const d = new Date(user.value.created_at.replace(' ', 'T'))
  return isNaN(d) ? '' : d.toLocaleDateString('zh-TW')
})

watch(() => route.fullPath, () => {
  userMenuOpen.value = false
  mobileOpen.value = false
})

function toggleUserMenu() {
  userMenuOpen.value = !userMenuOpen.value
}

function closeUserMenu() {
  userMenuOpen.value = false
  mobileOpen.value = false
}

function avatarError() {
  if (user.value) user.value.avatar = null
}

function startEdit() {
  editPhone.value = user.value.phone || ''
  editAddress.value = user.value.address || ''
  editing.value = true
}

function cancelEdit() {
  editing.value = false
}

function togglePwEdit() {
  pwEdit.value = !pwEdit.value
  if (!pwEdit.value) {
    pwOld.value = ''
    pwNew.value = ''
    pwConfirm.value = ''
  }
}

async function savePassword() {
  if (pwNew.value.length < 6) {
    toastStore.error('新密碼至少需 6 個字元')
    return
  }
  if (pwNew.value !== pwConfirm.value) {
    toastStore.error('兩次輸入的新密碼不一致')
    return
  }
  pwSaving.value = true
  const res = await session.changePassword(pwOld.value, pwNew.value)
  if (res.success) {
    toastStore.success(res.message)
    togglePwEdit()
  } else {
    toastStore.error(res.message)
  }
  pwSaving.value = false
}

async function saveEdit() {
  saving.value = true
  const res = await session.updateContact(editPhone.value, editAddress.value)
  if (res.success) editing.value = false
  saving.value = false
}

async function handleLogout() {
  await session.logout()
  userMenuOpen.value = false
  router.push('/')
}

onMounted(async () => {
  site.init()
  await session.fetch()
  document.addEventListener('click', closeUserMenu)
})

onBeforeUnmount(() => {
  site.dispose()
  document.removeEventListener('click', closeUserMenu)
})
</script>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Hanken Grotesk', 'Segoe UI', Arial, sans-serif; background: var(--shop-background); color: var(--shop-text); -webkit-font-smoothing: antialiased; }
#layout { display: flex; flex-direction: column; min-height: 100vh; }

#layout > nav { position: sticky; top: 0; z-index: 100; background: rgba(var(--shop-background-rgb), 0.8); color: var(--shop-text); display: flex; align-items: center; justify-content: space-between; padding: 14px 40px; border-bottom: 1px solid var(--shop-border); box-shadow: 0 20px 50px rgba(0, 228, 117, 0.1); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
#layout > nav .inner { width: 100%; display: flex; align-items: center; justify-content: space-between; }
#layout > nav .logo { font-size: 20px; font-weight: 700; color: var(--shop-primary); text-decoration: none; }
#layout > nav .logo i { color: var(--shop-primary); margin-right: 8px; }
.nav-links { display: flex; gap: 20px; align-items: center; }
.nav-links a { color: var(--shop-text-muted); text-decoration: none; font-size: 14px; transition: color 0.2s; }
.nav-links a:hover, .nav-links a.router-link-exact-active { color: var(--shop-text); }
.user-info { color: var(--shop-primary); font-size: 14px; font-weight: 600; cursor: pointer; user-select: none; }
.nav-links { position: relative; }
.nav-toggle { display: none; background: none; border: none; color: var(--shop-text); font-size: 22px; cursor: pointer; padding: 4px 6px; }
.user-card { position: absolute; top: 52px; right: 40px; width: 280px; background: var(--shop-surface-low); border: 1px solid var(--shop-border); border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.5); z-index: 200; overflow: hidden; animation: fade-in 0.15s ease; }
@keyframes fade-in { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
.uc-header { display: flex; align-items: center; gap: 12px; padding: 18px; background: #202020; }
.uc-avatar { width: 44px; height: 44px; border-radius: 50%; background: #75ff9e; color: #003918; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; object-fit: cover; }
.uc-name { color: #e5e2e1; font-weight: 700; font-size: 15px; }
.uc-provider { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; margin-top: 3px; padding: 2px 8px; border-radius: 999px; }
.uc-provider.p-local { background: rgba(255,255,255,0.12); color: #bacbb9; }
.uc-provider.p-google { background: rgba(66,133,244,0.25); color: #8ab4f8; }
.uc-provider.p-line { background: rgba(6,199,85,0.25); color: #7fe0ac; }
.uc-body { padding: 14px 18px; }
.uc-row { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #bacbb9; padding: 6px 0; }
.uc-row i { width: 16px; color: #859585; text-align: center; }
.uc-row a { color: #75ff9e; text-decoration: none; font-weight: 600; }
.uc-row span { word-break: break-all; }
.uc-edit input { flex: 1; padding: 6px 10px; background: #131313; border: 1px solid rgba(255,255,255,0.12); border-radius: 6px; font-size: 13px; outline: none; color: #e5e2e1; }
.uc-edit input:focus { border-color: #75ff9e; }
.uc-footer { display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 18px 16px; border-top: 1px solid rgba(255,255,255,0.08); }
.uc-pw { padding: 12px 18px; border-top: 1px solid rgba(255,255,255,0.08); background: #1b1b1c; }
.uc-pw-actions { margin-top: 10px; }
.btn-sm { padding: 8px 16px; font-size: 13px; }

#layout > .site-marquee { background: var(--shop-primary); color: var(--shop-on-primary); overflow: hidden; padding: 9px 0; border-bottom: 1px solid var(--shop-primary-strong); font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 700; letter-spacing: .08em; }
#layout > .site-marquee span { display: inline-block; white-space: nowrap; animation: marquee-scroll 20s linear infinite; }
@keyframes marquee-scroll {
  0% { transform: translateX(100vw); }
  100% { transform: translateX(-100%); }
}

main { flex: 1; max-width: 1200px; width: 100%; margin: 0 auto; padding: 30px 40px; }

footer { background: var(--shop-background); color: var(--shop-text-muted); text-align: center; padding: 20px; font-size: 13px; }

.btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 22px; border-radius: 8px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s; }
.btn-primary { background: var(--shop-primary); color: var(--shop-on-primary); }
.btn-primary:hover { background: var(--shop-primary-strong); }
.btn-default { background: var(--shop-surface-high); color: var(--shop-text); border: 1px solid var(--shop-border); }
.btn-default:hover { background: var(--shop-surface-highest); }
.btn-danger { background: #93000a; color: #ffdad6; }
.btn-danger:hover { background: #b4000c; }

.card { background: var(--shop-glass); border: 1px solid var(--shop-border); border-radius: 12px; padding: 24px; margin-bottom: 20px; }
.card h3 { color: var(--shop-text); }
.loader { display: inline-block; width: 28px; height: 28px; border: 2px solid var(--shop-border); border-top-color: var(--shop-primary); border-radius: 50%; animation: spin .8s linear infinite; }
.loader-sm { width: 16px; height: 16px; }
.loader-lg { width: 30px; height: 30px; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  #layout > nav { padding: 12px 16px; }
  #layout > nav .logo { font-size: 18px; }
  .page-home > footer { display: none; }
  .page-home > main { padding: 0; }
  .nav-toggle { display: block; }
  .nav-links {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    flex-direction: column;
    align-items: stretch;
    gap: 0;
    background: var(--shop-background);
    padding: 6px 16px 16px;
    box-shadow: 0 12px 24px rgba(0,0,0,0.3);
  }
  .nav-links:not(.open) { display: none; }
  .nav-links a, .nav-links .user-info {
    padding: 14px 4px;
    font-size: 15px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .nav-links a:last-child, .nav-links .user-info:last-child { border-bottom: none; }
  .nav-links a i, .nav-links .user-info i:first-child { width: 20px; text-align: center; }
  .nav-links .user-info { color: var(--shop-primary); }
  .user-card { position: static; width: 100%; margin-top: 6px; border-radius: 10px; box-shadow: none; }
  main { padding: 20px 16px; }
}
</style>
