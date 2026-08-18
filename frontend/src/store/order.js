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
    async pay(id) {
      this.paying = true
      const res = await api.payOrder(id)
      if (res.success && this.detail) this.detail.status = 'paid'
      this.paying = false
      return res
    },
    async placeOrder(items, receiver, remark) {
      const res = await api.createOrder(items, receiver, remark)
      if (res.success) useCartStore().clear()
      return res
    },
  },
})