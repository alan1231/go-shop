import { defineStore } from 'pinia'
import { api } from '../api/index.js'
import { useCartStore } from './cart.js'

export const useOrderStore = defineStore('order', {
  state: () => ({
    orders: [],
    status: '',
    loading: true,
    detail: null,
    detailLoading: true,
    paying: false,
    payment: null,
    pollTimer: null,
    polling: false,
    requestId: 0,
  }),
  actions: {
    async loadOrders(status) {
      this.status = status
      const requestId = ++this.requestId
      this.loading = true
      const res = await api.orders(status)
      if (requestId !== this.requestId) return
      if (res.success) this.orders = res.data
      this.loading = false
    },
    async loadDetail(id) {
      this.detail = null
      this.detailLoading = true
      const res = await api.order(id)
      if (res.success) this.detail = res.data
      this.detailLoading = false
      return res
    },
    async pay(id, method) {
      this.paying = true
      const res = await api.payOrder(id, method)
      if (res.success) this.payment = res.data
      this.paying = false
      return res
    },
    startPolling(id, onResult) {
      this.stopPolling()
      this.pollTimer = setInterval(async () => {
        if (this.polling) return
        this.polling = true
        const res = await api.payOrderStatus(id)
        this.polling = false
        if (!res.success) return
        if (res.data.status === 'paid') {
          this.stopPolling()
          this.payment = null
          await this.loadDetail(id)
          if (onResult) onResult(res)
        } else if (res.data.payment === 'cancelled') {
          this.stopPolling()
          this.payment = null
          await this.loadDetail(id)
          if (onResult) onResult(res)
        }
      }, 3000)
    },
    stopPolling() {
      this.polling = false
      if (this.pollTimer) {
        clearInterval(this.pollTimer)
        this.pollTimer = null
      }
    },
    async placeOrder(items, receiver, remark, tableNumber) {
      const res = await api.createOrder(items, receiver, remark, tableNumber)
      if (res.success) await useCartStore().clear()
      return res
    },
  },
})