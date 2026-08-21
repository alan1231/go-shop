import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'
import Products from '../views/Products.vue'
import ProductForm from '../views/ProductForm.vue'
import Orders from '../views/Orders.vue'
import OrderHistory from '../views/OrderHistory.vue'
import OrderDetail from '../views/OrderDetail.vue'
import Settings from '../views/Settings.vue'
import PointOrder from '../views/PointOrder.vue'
import Accounts from '../views/Accounts.vue'
import Account from '../views/Account.vue'

const routes = [
  { path: '/login', name: 'login', component: Login, meta: { title: '登入' } },
  { path: '/', name: 'dashboard', component: Dashboard, meta: { title: '儀表板', requiresAuth: true } },
  { path: '/products', name: 'products', component: Products, meta: { title: '菜單管理', requiresAuth: true } },
  { path: '/products/add', name: 'product-add', component: ProductForm, meta: { title: '新增菜單', requiresAuth: true } },
  { path: '/products/:id/edit', name: 'product-edit', component: ProductForm, meta: { title: '修改菜單', requiresAuth: true } },
  { path: '/orders', name: 'orders', component: Orders, meta: { title: '訂單管理', requiresAuth: true } },
  { path: '/orders/history', name: 'order-history', component: OrderHistory, meta: { title: '訂單歷程', requiresAuth: true } },
  { path: '/orders/:id', name: 'order', component: OrderDetail, meta: { title: '訂單明細', requiresAuth: true } },
  { path: '/settings', name: 'settings', component: Settings, meta: { title: '系統設定', requiresAuth: true } },
  { path: '/point-order', name: 'point-order', component: PointOrder, meta: { title: '新增訂單', requiresAuth: true } },
  { path: '/accounts', name: 'accounts', component: Accounts, meta: { title: '後台帳號', requiresAuth: true } },
  { path: '/account', name: 'account', component: Account, meta: { title: '帳號設定', requiresAuth: true } },
]

const router = createRouter({
  history: createWebHistory('/admin/'),
  routes,
})

router.beforeEach((to, from, next) => {
  document.title = (to.meta.title ? to.meta.title + ' - ' : '') + '點餐 後台'
  const token = localStorage.getItem('admin_token')
  if (to.meta.requiresAuth && !token) {
    next({ path: '/login' })
  } else {
    next()
  }
})

export default router
