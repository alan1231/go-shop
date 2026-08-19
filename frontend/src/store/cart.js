import { defineStore } from 'pinia'
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
    quantity: qty,
  })
}

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: guestCart.load(),
  }),
  getters: {
    count: state => state.items.reduce((s, i) => s + i.quantity, 0),
  },
  actions: {
    saveGuest() {
      guestCart.save(this.items)
    },
    add(product, qty = 1) {
      upsertItem(this.items, product, qty)
      this.saveGuest()
      return { ok: true, message: `「${product.name}」已加入購物車` }
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
    clear() {
      this.items.splice(0, this.items.length)
      guestCart.clear()
    },
  },
})
