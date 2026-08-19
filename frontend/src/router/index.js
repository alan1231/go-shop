import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import ProductDetail from '../views/ProductDetail.vue'
import Cart from '../views/Cart.vue'
import OrderDetail from '../views/OrderDetail.vue'

const routes = [
  { path: '/idnex', redirect: '/' },
  { path: '/index', redirect: '/' },
  { path: '/', name: 'home', component: Home, meta: { title: '首頁' } },
  { path: '/menu', name: 'menu', component: Home, meta: { title: '菜單' } },
  { path: '/products/:id', name: 'product', component: ProductDetail, meta: { title: '商品' } },
  { path: '/order', name: 'cart', component: Cart, meta: { title: '訂單' } },
  { path: '/orders/:id', name: 'order', component: OrderDetail, meta: { title: '訂單' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  document.title = (to.meta.title ? to.meta.title + ' - ' : '') + 'SHOP'
  return true
})

export default router
