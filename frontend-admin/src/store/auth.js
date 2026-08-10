import { reactive } from 'vue'
import { api } from '../api/index.js'

export const authStore = reactive({
  admin: null,
  async fetch() {
    if (!localStorage.getItem('admin_token')) {
      this.admin = null
      return
    }
    const res = await api.me()
    this.admin = res.success ? res.data : null
  },
  set(admin) {
    this.admin = admin
  },
  clear() {
    this.admin = null
    localStorage.removeItem('admin_token')
  },
})
