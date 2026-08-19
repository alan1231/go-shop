import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'
import ProductDetail from '../views/ProductDetail.vue'
import Cart from '../views/Cart.vue'
import Orders from '../views/Orders.vue'
import OrderDetail from '../views/OrderDetail.vue'
import OrderSuccess from '../views/OrderSuccess.vue'
import Register from '../views/Register.vue'
import OAuthCallback from '../views/OAuthCallback.vue'
import { pinia } from '../pinia'
import { useSessionStore } from '../store/session.js'

const routes = [
  { path: '/idnex', redirect: '/' },
  { path: '/index', redirect: '/' },
  { path: '/', name: 'home', component: Home, meta: { title: '首頁' } },
  { path: '/login', name: 'login', component: Login, meta: { title: '登入' } },
  { path: '/register', name: 'register', component: Register, meta: { title: '註冊' } },
  { path: '/auth/callback', name: 'oauth-callback', component: OAuthCallback, meta: { title: '登入中' } },
  { path: '/products/:id', name: 'product', component: ProductDetail, meta: { title: '商品' } },
  { path: '/cart', name: 'cart', component: Cart, meta: { title: '購物車' } },
  { path: '/orders', name: 'orders', component: Orders, meta: { title: '我的訂單', requiresAuth: true } },
  { path: '/orders/:id', name: 'order', component: OrderDetail, meta: { title: '訂單', requiresAuth: true } },
  { path: '/orders/:id/success', name: 'order-success', component: OrderSuccess, meta: { title: '訂購成功' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  document.title = (to.meta.title ? to.meta.title + ' - ' : '') + 'SHOP'
  if (to.meta.requiresAuth && !useSessionStore(pinia).isLoggedIn) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }
  return true
})

export default router