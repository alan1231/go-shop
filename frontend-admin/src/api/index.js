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
  moveCategory(name, direction) {
    return request('/admin/categories/move', {
      method: 'POST',
      body: JSON.stringify({ name, direction }),
    })
  },
  reorderCategories(order) {
    return request('/admin/categories/order', {
      method: 'POST',
      body: JSON.stringify({ order }),
    })
  },
  createProduct(form) {
    return requestForm('/admin/products', form)
  },
  updateProduct(id, form) {
    return requestForm(`/admin/products/${id}`, form)
  },
  deleteProduct(id) {
    return request(`/admin/products/${id}/delete`, { method: 'POST' })
  },
  reorderProducts(order) {
    return request('/admin/products/order', {
      method: 'POST',
      body: JSON.stringify({ order }),
    })
  },

  orders(params = {}) {
    const qs = new URLSearchParams()
    if (params.status) qs.set('status', params.status)
    if (params.page) qs.set('page', params.page)
    if (params.per_page) qs.set('per_page', params.per_page)
    if (params.with_items) qs.set('with_items', '1')
    if (params.start) qs.set('start', params.start)
    if (params.end) qs.set('end', params.end)
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
  updateOrderItems(id, items) {
    return request(`/admin/orders/${id}/items`, {
      method: 'POST',
      body: JSON.stringify({ items }),
    })
  },
  createOrder(payload) {
    return request('/admin/orders', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
  },

  accounts() {
    return request('/admin/accounts')
  },
  createAccount(user) {
    return request('/admin/accounts', {
      method: 'POST',
      body: JSON.stringify(user),
    })
  },
  deleteAccount(id) {
    return request(`/admin/accounts/${id}/delete`, { method: 'POST' })
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
  updateLinePay(linepay) {
    return request('/admin/settings/linepay', {
      method: 'POST',
      body: JSON.stringify(linepay),
    })
  },
  updateMenuLayout(menuLayout) {
    return request('/admin/settings/menu-layout', {
      method: 'POST',
      body: JSON.stringify({ menu_layout: menuLayout }),
    })
  },
}
