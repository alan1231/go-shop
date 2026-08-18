import { defineStore } from 'pinia'
import { api } from '../api/index.js'
import { useSessionStore } from './session.js'
import { guestCart } from './guestCart.js'

function upsertItem(items, product, qty) {
  const exist = items.find(i => i.product_id === product.id)
  if (exist) {
    if (!exist.image && product.image) exist.image = product.image
    exist.quantity += qty
    return exist
  }
  items.push({
    product_id: product.id,
    name: product.name,
    image: product.image,
    price: product.price,
    stock: product.stock,
    quantity: qty,
  })
}

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: guestCart.load(),
  }),
  getters: {
    count: state => state.items.reduce((s, i) => s + i.quantity, 0),
    isRemote: () => useSessionStore().isLoggedIn,
  },
  actions: {
    persist() {
      if (!this.isRemote) guestCart.save(this.items)
    },
    saveGuest() {
      if (!this.isRemote) guestCart.save(this.items)
    },
    async sync() {
      if (this.isRemote) {
        const guest = guestCart.load()
        if (guest.length) {
          const res = await api.cartMerge(guest)
          if (res.success) guestCart.clear()
        }
        const res = await api.cart()
        if (res.success) this.items = res.data
      } else {
        this.items = guestCart.load()
      }
    },
    async add(product, qty = 1) {
      if (this.isRemote) {
        const res = await api.cartAdd(product.id, qty)
        if (!res.success) return { ok: false, message: res.message }
        upsertItem(this.items, product, qty)
        return { ok: true, message: `「${product.name}」已加入購物車` }
      }
      const stock = product.stock
      const exist = this.items.find(i => i.product_id === product.id)
      if (!exist && stock < 1) {
        return { ok: false, message: `「${product.name}」已售完` }
      }
      const next = (exist ? exist.quantity : 0) + qty
      if (next > stock) {
        return { ok: false, message: `「${product.name}」庫存不足（最多 ${stock} 件）` }
      }
      upsertItem(this.items, product, qty)
      this.persist()
      return { ok: true, message: `「${product.name}」已加入購物車` }
    },
    async changeQty(index, delta) {
      const item = this.items[index]
      if (!item) return { ok: false }
      const next = item.quantity + delta
      if (next < 1) return { ok: false }
      if (this.isRemote) {
        const res = await api.cartSet(item.product_id, next)
        if (!res.success) return { ok: false, message: res.message }
        item.quantity = next
        return { ok: true }
      }
      if (delta > 0 && next > item.stock) {
        return { ok: false, message: `庫存不足（最多 ${item.stock} 件）` }
      }
      item.quantity = next
      this.persist()
      return { ok: true }
    },
    async remove(index) {
      const [item] = this.items.splice(index, 1)
      if (this.isRemote) {
        if (item) await api.cartRemove(item.product_id)
      } else {
        this.persist()
      }
    },
    async clear() {
      this.items.splice(0, this.items.length)
      if (this.isRemote) await api.cartClear()
      else this.persist()
    },
  },
})