import { defineStore } from 'pinia'
import { api } from '../api/index.js'

export const useSessionStore = defineStore('session', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token'),
  }),
  getters: {
    isLoggedIn: state => !!state.token,
  },
  actions: {
    async fetch() {
      if (!this.isLoggedIn) {
        this.user = null
        return
      }
      const res = await api.me()
      this.user = res.success ? res.data : null
    },
    set(u) {
      this.user = u
    },
    async login(username, password) {
      const res = await api.login(username, password)
      if (res.success) {
        this.user = res.data.user
        this.token = res.data.token
      }
      return res
    },
    async register(username, email, password) {
      const res = await api.register(username, email, password)
      return res
    },
    async completeOAuth(provider, code) {
      const res = await api.oauthLogin(provider, code)
      if (res.success) {
        this.user = res.data.user
        this.token = res.data.token
      }
      return res
    },
    async updateContact(phone, address) {
      const res = await api.updateContact(phone, address)
      if (!res.success) return res
      await this.fetch()
      return res
    },
    changePassword(oldPassword, newPassword) {
      return api.changePassword(oldPassword, newPassword)
    },
    async logout() {
      await api.logout()
      localStorage.removeItem('token')
      this.token = null
      this.user = null
    },
  },
})