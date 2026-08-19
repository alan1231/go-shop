const BASE = '/api'

async function request(url, options = {}) {
  const headers = { 'Content-Type': 'application/json', ...options.headers }

  let res
  try {
    res = await fetch(BASE + url, { headers, ...options })
  } catch (e) {
    return { success: false, message: '網路連線失敗，請稍後再試' }
  }

  let data
  try {
    data = await res.json()
  } catch (e) {
    return { success: false, message: '伺服器回應異常，請稍後再試' }
  }

  if (!res.ok && !data.success) {
    return { success: false, message: data.message || '請求失敗，請稍後再試' }
  }

  return data
}

export const api = {
  // Products
  products(params = {}) {
    const qs = new URLSearchParams()
    if (params.q) qs.set('q', params.q)
    if (params.category) qs.set('category', params.category)
    if (params.page) qs.set('page', params.page)
    if (params.per_page) qs.set('per_page', params.per_page)
    const s = qs.toString()
    return request('/products' + (s ? '?' + s : ''))
  },
  categories() {
    return request('/categories')
  },
  product(id) {
    return request(`/products/${id}`)
  },

  // Tables
  availableTable() {
    return request('/tables/available')
  },

  // Orders
  createOrder(items, receiver = {}, remark = '', tableNumber = 0, orderType = 'dine_in') {
    return request('/orders', {
      method: 'POST',
      body: JSON.stringify({ items, receiver, remark, table_number: tableNumber, order_type: orderType }),
    })
  },
  order(id) {
    return request(`/orders/${id}`)
  },
  payOrder(id, method) {
    return request(`/orders/${id}/pay`, {
      method: 'POST',
      body: JSON.stringify({ method: method || 'linepay' }),
    })
  },
  payOrderStatus(id) {
    return request(`/orders/${id}/pay/status`)
  },

  // Marquee
  marquee() {
    return request('/marquee')
  },
}