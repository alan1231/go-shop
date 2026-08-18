export function money(value) {
  return Number(value || 0).toLocaleString()
}

export function imageUrl(image) {
  if (!image || image.startsWith('/') || image.startsWith('http')) return image
  return `/uploads/${image}`
}

export function formatDate(value, { separator = '.', time = false, empty = '' } = {}) {
  if (!value) return empty
  const date = new Date(value.replace(' ', 'T'))
  if (isNaN(date)) return value
  const pad = number => String(number).padStart(2, '0')
  const day = [date.getFullYear(), pad(date.getMonth() + 1), pad(date.getDate())].join(separator)
  return time ? `${day} ${pad(date.getHours())}:${pad(date.getMinutes())}` : day
}

export function orderStatusLabel(status, shipped = '出貨中') {
  return { pending: '待付款', paid: '已付款', shipped, completed: '已完成', cancelled: '已取消' }[status] || status
}

export function paymentMethodLabel(method) {
  return { linepay: 'LINE Pay', cod: '貨到付款' }[method] || '未指定'
}
