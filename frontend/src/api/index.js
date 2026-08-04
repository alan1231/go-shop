const BASE = '/api'

function getToken() {
  return localStorage.getItem('token')
}

async function request(url, options = {}) {
  const headers = { 'Content-Type': 'application/json', ...options.headers }
  const token = getToken()
  if (token) headers['Authorization'] = 'Bearer ' + token

  const res = await fetch(BASE + url, {
    credentials: 'include',
    headers,
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
  async login(username, password) {
    const res = await request('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    })
    if (res.success && res.data.token) {
      localStorage.setItem('token', res.data.token)
    }
    return res
  },
  async oauthLogin(provider, code) {
    const res = await request('/auth/oauth', {
      method: 'POST',
      body: JSON.stringify({ provider, code }),
    })
    if (res.success && res.data.token) {
      localStorage.setItem('token', res.data.token)
    }
    return res
  },
  logout() {
    return request('/auth/logout', { method: 'POST' })
  },
  me() {
    return request('/auth/me')
  },
  updateContact(phone, address) {
    return request('/auth/update', {
      method: 'POST',
      body: JSON.stringify({ phone, address }),
    })
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
  payOrder(id) {
    return request(`/orders/${id}/pay`, { method: 'POST' })
  },

  // Marquee
  marquee() {
    return request('/marquee')
  },
}
