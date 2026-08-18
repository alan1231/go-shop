import { defineStore } from 'pinia'

const CART_KEY = 'shop_cart'

function loadItems() {
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
    items: loadItems(),
  }),
  getters: {
    count: (state) => state.items.reduce((s, i) => s + i.quantity, 0),
  },
  actions: {
    save() {
      localStorage.setItem(CART_KEY, JSON.stringify(this.items))
    },
    add(product) {
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
      this.save()
      return { ok: true, message: `「${product.name}」已加入購物車` }
    },
    changeQty(index, delta) {
      const item = this.items[index]
      const next = item.quantity + delta
      if (next < 1) return { ok: false }
      if (delta > 0 && next > item.stock) {
        return { ok: false, message: `庫存不足（最多 ${item.stock} 件）` }
      }
      item.quantity = next
      this.save()
      return { ok: true }
    },
    remove(index) {
      this.items.splice(index, 1)
      this.save()
    },
    clear() {
      this.items.splice(0, this.items.length)
      this.save()
    },
  },
})