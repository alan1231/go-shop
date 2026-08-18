import { defineStore } from 'pinia'

export const useToastStore = defineStore('toast', {
  state: () => ({
    items: [],
  }),
  actions: {
    show(message, type = 'success') {
      const id = Date.now() + Math.random()
      this.items.push({ id, message, type })
      setTimeout(() => this.remove(id), 3000)
    },
    success(message) { this.show(message, 'success') },
    error(message) { this.show(message, 'error') },
    remove(id) {
      const i = this.items.findIndex(t => t.id === id)
      if (i !== -1) this.items.splice(i, 1)
    },
  },
})