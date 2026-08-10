import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'
import Products from '../views/Products.vue'
import ProductForm from '../views/ProductForm.vue'
import Orders from '../views/Orders.vue'
import OrderDetail from '../views/OrderDetail.vue'
import Users from '../views/Users.vue'
import UserForm from '../views/UserForm.vue'
import Marquee from '../views/Marquee.vue'

const routes = [
  { path: '/login', name: 'login', component: Login, meta: { title: '登入' } },
  { path: '/', name: 'dashboard', component: Dashboard, meta: { title: '儀表板', requiresAuth: true } },
  { path: '/products', name: 'products', component: Products, meta: { title: '商品管理', requiresAuth: true } },
  { path: '/products/add', name: 'product-add', component: ProductForm, meta: { title: '新增商品', requiresAuth: true } },
  { path: '/products/:id/edit', name: 'product-edit', component: ProductForm, meta: { title: '修改商品', requiresAuth: true } },
  { path: '/orders', name: 'orders', component: Orders, meta: { title: '訂單管理', requiresAuth: true } },
  { path: '/orders/:id', name: 'order', component: OrderDetail, meta: { title: '訂單明細', requiresAuth: true } },
  { path: '/users', name: 'users', component: Users, meta: { title: '會員管理', requiresAuth: true } },
  { path: '/users/add', name: 'user-add', component: UserForm, meta: { title: '新增會員', requiresAuth: true } },
  { path: '/marquee', name: 'marquee', component: Marquee, meta: { title: '跑馬燈', requiresAuth: true } },
]

const router = createRouter({
  history: createWebHistory('/admin/'),
  routes,
})

router.beforeEach((to, from, next) => {
  document.title = (to.meta.title ? to.meta.title + ' - ' : '') + 'SHOP 後台'
  const token = localStorage.getItem('admin_token')
  if (to.meta.requiresAuth && !token) {
    next({ path: '/login' })
  } else {
    next()
  }
})

export default router
