const BASE = '/api'

async function request(url, options = {}) {
  const res = await fetch(BASE + url, {
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', ...options.headers },
    ...options,
  })
  return res.json()
}

export const api = {
  // Auth
  register(username, email, password) {
    return request('/auth/register', {
      method: 'POST',
      body: JSON.stringify({ username, email, password }),
    })
  },
  login(username, password) {
    return request('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    })
  },
  logout() {
    return request('/auth/logout', { method: 'POST' })
  },
  me() {
    return request('/auth/me')
  },

  // Products
  products() {
    return request('/products')
  },
  product(id) {
    return request(`/products/${id}`)
  },

  // Orders
  createOrder(items) {
    return request('/orders', {
      method: 'POST',
      body: JSON.stringify({ items }),
    })
  },
  orders() {
    return request('/orders')
  },
  order(id) {
    return request(`/orders/${id}`)
  },

  // Marquee
  marquee() {
    return request('/marquee')
  },
}
