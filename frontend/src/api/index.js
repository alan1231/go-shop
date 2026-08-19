const BASE = '/api'

function getToken() {
  return localStorage.getItem('token')
}

function clearAuth() {
  localStorage.removeItem('token')
  const { pathname, search } = window.location
  const isAuthPage = ['/login', '/register', '/auth/callback'].some(p => pathname.startsWith(p))
  if (!isAuthPage) {
    window.location.href = '/login?redirect=' + encodeURIComponent(pathname + search)
  }
}

async function request(url, options = {}) {
  const headers = { 'Content-Type': 'application/json', ...options.headers }
  const token = getToken()
  if (token) headers['Authorization'] = 'Bearer ' + token

  let res
  try {
    res = await fetch(BASE + url, { headers, ...options })
  } catch (e) {
    return { success: false, message: '網路連線失敗，請稍後再試' }
  }

  if (res.status === 401) {
    clearAuth()
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
      body: JSON.stringify({
        provider,
        code,
        redirect_uri: window.location.origin + '/auth/callback',
      }),
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
  changePassword(oldPassword, newPassword) {
    return request('/auth/password', {
      method: 'POST',
      body: JSON.stringify({ old_password: oldPassword, new_password: newPassword }),
    })
  },

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

  // Orders
  createOrder(items, receiver = {}, remark = '') {
    return request('/orders', {
      method: 'POST',
      body: JSON.stringify({ items, receiver, remark }),
    })
  },
  orders(status = '') {
    const s = status ? '?status=' + encodeURIComponent(status) : ''
    return request('/orders' + s)
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

  // Cart
  cart() {
    return request('/cart')
  },
  cartAdd(productId, quantity) {
    return request('/cart/items', {
      method: 'POST',
      body: JSON.stringify({ product_id: productId, quantity }),
    })
  },
  cartSet(productId, quantity) {
    return request(`/cart/items/${productId}`, {
      method: 'PUT',
      body: JSON.stringify({ quantity }),
    })
  },
  cartRemove(productId) {
    return request(`/cart/items/${productId}`, { method: 'DELETE' })
  },
  cartClear() {
    return request('/cart', { method: 'DELETE' })
  },
  cartMerge(items) {
    return request('/cart/merge', {
      method: 'POST',
      body: JSON.stringify({ items }),
    })
  },

  // Marquee
  marquee() {
    return request('/marquee')
  },
}
