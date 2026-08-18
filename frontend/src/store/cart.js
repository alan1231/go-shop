import { defineStore } from 'pinia'
import { api } from '../api/index.js'
import { useSessionStore } from './session.js'

const CART_KEY = 'shop_cart'

function loadGuestItems() {
  try {
    const items = JSON.parse(localStorage.getItem(CART_KEY) || '[]')
    if (!Array.isArray(items)) return []
    return items.filter(i => i && Number.isFinite(Number(i.product_id)))
  } catch {
    return []
  }
}

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: loadGuestItems(),
  }),
  getters: {
    count: state => state.items.reduce((s, i) => s + i.quantity, 0),
  },
  actions: {
    isServer() {
      return useSessionStore().isLoggedIn
    },
    saveGuest() {
      if (this.isServer()) return
      localStorage.setItem(CART_KEY, JSON.stringify(this.items))
    },
    async sync() {
      if (this.isServer()) {
        const guest = loadGuestItems()
        if (guest.length) {
          const res = await api.cartMerge(guest)
          if (res.success) localStorage.setItem(CART_KEY, '[]')
        }
        await this.loadRemote()
      } else {
        this.items = loadGuestItems()
      }
    },
    async loadRemote() {
      const res = await api.cart()
      if (res.success) this.items = res.data
    },
    async add(product) {
      if (this.isServer()) {
        const res = await api.cartAdd(product.id, 1)
        if (!res.success) return { ok: false, message: res.message }
        const exist = this.items.find(i => i.product_id === product.id)
        if (exist) {
          if (!exist.image && product.image) exist.image = product.image
          exist.quantity++
        } else {
          this.items.push({ product_id: product.id, name: product.name, image: product.image, price: product.price, stock: product.stock, quantity: 1 })
        }
        return { ok: true, message: `「${product.name}」已加入購物車` }
      }
      const stock = product.stock
      const exist = this.items.find(i => i.product_id === product.id)
      if (exist) {
        if (!exist.image && product.image) exist.image = product.image
        if (exist.quantity + 1 > stock) {
          return { ok: false, message: `「${product.name}」庫存不足（最多 ${stock} 件）` }
        }
        exist.quantity++
      } else {
        if (stock < 1) {
          return { ok: false, message: `「${product.name}」已售完` }
        }
        this.items.push({ product_id: product.id, name: product.name, image: product.image, price: product.price, stock, quantity: 1 })
      }
      this.saveGuest()
      return { ok: true, message: `「${product.name}」已加入購物車` }
    },
    async changeQty(index, delta) {
      const item = this.items[index]
      const next = item.quantity + delta
      if (next < 1) return { ok: false }
      if (this.isServer()) {
        const res = await api.cartSet(item.product_id, next)
        if (!res.success) return { ok: false, message: res.message }
        item.quantity = next
        return { ok: true }
      }
      if (delta > 0 && next > item.stock) {
        return { ok: false, message: `庫存不足（最多 ${item.stock} 件）` }
      }
      item.quantity = next
      this.saveGuest()
      return { ok: true }
    },
    async remove(index) {
      const [item] = this.items.splice(index, 1)
      if (this.isServer()) {
        if (item) await api.cartRemove(item.product_id)
      } else {
        this.saveGuest()
      }
    },
    async clear() {
      this.items.splice(0, this.items.length)
      if (this.isServer()) await api.cartClear()
      else this.saveGuest()
    },
  },
})