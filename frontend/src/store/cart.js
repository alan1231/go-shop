import { reactive, computed } from 'vue'

const saved = JSON.parse(localStorage.getItem('cart') || '[]')

export const cartStore = reactive({
  items: saved,
  count: computed(() => cartStore.items.reduce((s, i) => s + i.quantity, 0)),
  save() {
    localStorage.setItem('cart', JSON.stringify(cartStore.items))
  },
  add(product) {
    const exist = cartStore.items.find(i => i.product_id === product.id)
    if (exist) {
      exist.quantity++
    } else {
      cartStore.items.push({ product_id: product.id, name: product.name, price: product.price, quantity: 1 })
    }
    cartStore.save()
  },
  changeQty(index, delta) {
    cartStore.items[index].quantity = Math.max(1, cartStore.items[index].quantity + delta)
    cartStore.save()
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
