import { defineStore } from 'pinia'
import { api } from '../api/index.js'
import { guestCart } from './guestCart.js'
import { guestDineLoad, guestDineSave, guestDineClear } from './guestDine.js'

let placedOrderId = null

function upsertItem(items, product, qty, itemRemark) {
  const remarkProvided = itemRemark !== undefined
  const exist = items.find(i => i.product_id === product.id)
  if (exist) {
    if (!exist.image && product.image) exist.image = product.image
    exist.quantity += qty
    if (remarkProvided) exist.remark = itemRemark
    return exist
  }
  items.push({
    product_id: product.id,
    name: product.name,
    image: product.image,
    price: product.price,
    quantity: qty,
    ...(remarkProvided ? { remark: itemRemark } : {}),
  })
}

export const useOrderStore = defineStore('order', {
  state: () => ({
    items: guestCart.load(),
    tableNumber: guestDineLoad().tableNumber,
    orderType: guestDineLoad().orderType,
    detail: null,
    detailLoading: true,
    paying: false,
    payment: null,
    pollTimer: null,
    polling: false,
  }),
  getters: {
    count: state => state.items.reduce((s, i) => s + i.quantity, 0),
  },
  actions: {
    saveGuest() {
      guestCart.save(this.items)
    },
    add(product, qty = 1, itemRemark = undefined) {
      upsertItem(this.items, product, qty, itemRemark)
      this.saveGuest()
      return { ok: true, message: `「${product.name}」已加入訂單` }
    },
    changeQty(index, delta) {
      const item = this.items[index]
      if (!item) return { ok: false }
      const next = item.quantity + delta
      if (next < 1) return { ok: false }
      item.quantity = next
      this.saveGuest()
      return { ok: true }
    },
    remove(index) {
      this.items.splice(index, 1)
      this.saveGuest()
    },
    pruneInvalid(products) {
      const ids = new Set(products.map(p => p.id))
      const before = this.items.length
      this.items = this.items.filter(i => ids.has(i.product_id))
      if (this.items.length !== before) this.saveGuest()
      return before - this.items.length
    },
    clear() {
      this.items.splice(0, this.items.length)
      guestCart.clear()
    },
    setDine(tableNumber, orderType) {
      this.tableNumber = Number(tableNumber) || 0
      this.orderType = orderType === 'takeout' ? 'takeout' : 'dine_in'
      guestDineSave(this.tableNumber, this.orderType)
    },
    clearDine() {
      this.tableNumber = 0
      this.orderType = 'dine_in'
      guestDineClear()
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
    async placeOrder(items, receiver, remark, tableNumber, orderType) {
      const res = await api.createOrder(items, receiver, remark, tableNumber, orderType)
      if (res.success) {
        placedOrderId = Number(res.data?.order_id) || null
        this.clear()
        this.clearDine()
      }
      return res
    },
    isJustPlaced(id) {
      return placedOrderId === Number(id)
    },
  },
})