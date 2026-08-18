const KEY = 'shop_cart'

export const guestCart = {
  load() {
    try {
      const items = JSON.parse(localStorage.getItem(KEY) || '[]')
      if (!Array.isArray(items)) return []
      return items.filter(i => i && Number.isFinite(Number(i.product_id)))
    } catch {
      return []
    }
  },
  save(items) {
    localStorage.setItem(KEY, JSON.stringify(items))
  },
  clear() {
    localStorage.removeItem(KEY)
  },
}