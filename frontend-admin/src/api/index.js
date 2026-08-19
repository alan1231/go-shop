const BASE = '/api'

function getToken() {
  return localStorage.getItem('admin_token')
}

function setToken(t) {
  if (t) localStorage.setItem('admin_token', t)
  else localStorage.removeItem('admin_token')
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
    setToken('')
    if (!location.pathname.includes('/login')) {
      location.href = '/admin/login'
    }
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

async function requestForm(url, form) {
  const headers = {}
  const token = getToken()
  if (token) headers['Authorization'] = 'Bearer ' + token

  let res
  try {
    res = await fetch(BASE + url, { method: 'POST', headers, body: form })
  } catch (e) {
    return { success: false, message: '網路連線失敗，請稍後再試' }
  }

  if (res.status === 401) {
    setToken('')
    if (!location.pathname.includes('/login')) {
      location.href = '/admin/login'
    }
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
  login(username, password) {
    return request('/admin/login', {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    })
  },
  oauthLogin(provider, code, redirectUri) {
    return request('/admin/oauth', {
      method: 'POST',
      body: JSON.stringify({ provider, code, redirect_uri: redirectUri }),
    })
  },
  oauthBind(provider, code, redirectUri) {
    return request('/admin/oauth/bind', {
      method: 'POST',
      body: JSON.stringify({ provider, code, redirect_uri: redirectUri }),
    })
  },
  oauthUnbind(provider) {
    return request('/admin/oauth/unbind', {
      method: 'POST',
      body: JSON.stringify({ provider }),
    })
  },
  me() {
    return request('/admin/me')
  },
  logout() {
    return request('/admin/logout', { method: 'POST' })
  },
  stats() {
    return request('/admin/stats')
  },

  products(params = {}) {
    const qs = new URLSearchParams()
    if (params.q) qs.set('q', params.q)
    if (params.category) qs.set('category', params.category)
    if (params.page) qs.set('page', params.page)
    if (params.per_page) qs.set('per_page', params.per_page)
    const s = qs.toString()
    return request('/admin/products' + (s ? '?' + s : ''))
  },
  product(id) {
    return request(`/admin/products/${id}`)
  },
  categories() {
    return request('/admin/categories')
  },
  createProduct(form) {
    return requestForm('/admin/products', form)
  },
  updateProduct(id, form) {
    return requestForm(`/admin/products/${id}`, form)
  },

  orders(params = {}) {
    const qs = new URLSearchParams()
    if (params.status) qs.set('status', params.status)
    if (params.page) qs.set('page', params.page)
    const s = qs.toString()
    return request('/admin/orders' + (s ? '?' + s : ''))
  },
  order(id) {
    return request(`/admin/orders/${id}`)
  },
  updateOrderStatus(id, status) {
    return request(`/admin/orders/${id}/status`, {
      method: 'POST',
      body: JSON.stringify({ status }),
    })
  },
  updateOrderRemark(id, remark) {
    return request(`/admin/orders/${id}/remark`, {
      method: 'POST',
      body: JSON.stringify({ remark }),
    })
  },
  createOrder(payload) {
    return request('/admin/orders', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
  },

  users(q = '') {
    const s = q ? '?q=' + encodeURIComponent(q) : ''
    return request('/admin/users' + s)
  },
  user(id) {
    return request(`/admin/users/${id}`)
  },
  createUser(user) {
    return request('/admin/users', {
      method: 'POST',
      body: JSON.stringify(user),
    })
  },
  updateUserPassword(id, password) {
    return request(`/admin/users/${id}`, {
      method: 'POST',
      body: JSON.stringify({ password }),
    })
  },
  deleteUser(id) {
    return request(`/admin/users/${id}/delete`, { method: 'POST' })
  },

  marquee() {
    return request('/admin/marquee')
  },
  updateMarquee(content) {
    return request('/admin/marquee', {
      method: 'POST',
      body: JSON.stringify({ content }),
    })
  },
  settings() {
    return request('/admin/settings')
  },
  updateTableCount(tableCount) {
    return request('/admin/settings/table-count', {
      method: 'POST',
      body: JSON.stringify({ table_count: tableCount }),
    })
  },
}
