import { defineStore } from 'pinia'
import { api } from '../api/index.js'

export const useSiteStore = defineStore('site', {
  state: () => ({
    marqueeText: '',
    timer: null,
  }),
  actions: {
    async fetchMarquee() {
      const res = await api.marquee()
      if (res.success) this.marqueeText = res.data.content
    },
    init() {
      this.fetchMarquee()
      if (this.timer) clearInterval(this.timer)
      this.timer = setInterval(() => this.fetchMarquee(), 30000)
    },
    dispose() {
      if (this.timer) clearInterval(this.timer)
      this.timer = null
    },
  },
})