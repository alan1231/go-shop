const KEY = 'shp_dine'

export function guestDineLoad() {
  try {
    const raw = JSON.parse(localStorage.getItem(KEY) || '{}')
    const tableNumber = Number(raw.tableNumber) || 0
    const orderType = raw.orderType === 'takeout' ? 'takeout' : 'dine_in'
    return { tableNumber, orderType }
  } catch {
    return { tableNumber: 0, orderType: 'dine_in' }
  }
}

export function guestDineSave(tableNumber, orderType) {
  localStorage.setItem(KEY, JSON.stringify({ tableNumber: Number(tableNumber) || 0, orderType }))
}

export function guestDineClear() {
  localStorage.removeItem(KEY)
}