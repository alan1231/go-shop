import { reactive, computed } from 'vue'

const saved = JSON.parse(localStorage.getItem('cart') || '[]')

export const cartStore = reactive({
  items: saved,
  count: computed(() => cartStore.items.reduce((s, i) => s + i.quantity, 0)),
  save() {
    localStorage.setItem('cart', JSON.stringify(cartStore.items))
  },
  add(product) {
    const stock = product.stock
    const exist = cartStore.items.find(i => i.product_id === product.id)
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
      cartStore.items.push({ product_id: product.id, name: product.name, image: product.image, price: product.price, stock, quantity: 1 })
    }
    cartStore.save()
    return { ok: true, message: `「${product.name}」已加入購物車` }
  },
  changeQty(index, delta) {
    const item = cartStore.items[index]
    const next = item.quantity + delta
    if (next < 1) return { ok: false }
    if (delta > 0 && next > item.stock) {
      return { ok: false, message: `庫存不足（最多 ${item.stock} 件）` }
    }
    item.quantity = next
    cartStore.save()
    return { ok: true }
  },
  remove(index) {
    cartStore.items.splice(index, 1)
    cartStore.save()
  },
  clear() {
    cartStore.items.splice(0, cartStore.items.length)
    cartStore.save()
  },
})
