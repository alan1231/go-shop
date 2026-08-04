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
  { path: '/', name: 'home', component: Home },
  { path: '/login', name: 'login', component: Login },
  { path: '/register', name: 'register', component: Register },
  { path: '/auth/callback', name: 'oauth-callback', component: OAuthCallback },
  { path: '/products/:id', name: 'product', component: ProductDetail },
  { path: '/cart', name: 'cart', component: Cart },
  { path: '/orders', name: 'orders', component: Orders },
  { path: '/orders/:id', name: 'order', component: OrderDetail },
]

export default createRouter({
  history: createWebHistory(),
  routes,
})
