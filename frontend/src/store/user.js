import { reactive } from 'vue'
import { api } from '../api/index.js'

export const userStore = reactive({
  user: null,
  async fetch() {
    if (!localStorage.getItem('token')) {
      this.user = null
      return
    }
    const res = await api.me()
    this.user = res.success ? res.data : null
  },
  set(u) {
    this.user = u
  },
  clear() {
    this.user = null
  },
})
