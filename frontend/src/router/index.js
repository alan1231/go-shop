import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'
import ProductDetail from '../views/ProductDetail.vue'
import Cart from '../views/Cart.vue'
import Orders from '../views/Orders.vue'
import OrderDetail from '../views/OrderDetail.vue'
import Register from '../views/Register.vue'
import OAuthCallback from '../views/OAuthCallback.vue'

const routes = [
  { path: '/', name: 'home', component: Home, meta: { title: '首頁' } },
  { path: '/login', name: 'login', component: Login, meta: { title: '登入' } },
  { path: '/register', name: 'register', component: Register, meta: { title: '註冊' } },
  { path: '/auth/callback', name: 'oauth-callback', component: OAuthCallback, meta: { title: '登入中' } },
  { path: '/products/:id', name: 'product', component: ProductDetail, meta: { title: '商品' } },
  { path: '/cart', name: 'cart', component: Cart, meta: { title: '購物車' } },
  { path: '/orders', name: 'orders', component: Orders, meta: { title: '我的訂單', requiresAuth: true } },
  { path: '/orders/:id', name: 'order', component: OrderDetail, meta: { title: '訂單', requiresAuth: true } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  document.title = (to.meta.title ? to.meta.title + ' - ' : '') + 'SHOP'
  const token = localStorage.getItem('token')
  if (to.meta.requiresAuth && !token) {
    next({ path: '/login', query: { redirect: to.fullPath } })
  } else {
    next()
  }
})

export default router
