<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-box"></i> 商品列表</h1>
      <router-link to="/products/add" class="btn btn-primary"><i class="fas fa-plus"></i> 新增商品</router-link>
    </div>

    <div class="card filter-bar">
      <input type="text" v-model="q" placeholder="搜尋商品名稱或描述..." @keyup.enter="search" />
      <select v-model="category">
        <option value="">全部分類</option>
        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
      </select>
      <button class="btn btn-primary" @click="search"><i class="fas fa-search"></i> 篩選</button>
      <button v-if="q || category" class="btn btn-default" @click="reset">清除</button>
    </div>

    <div class="card" style="padding:0;overflow:hidden;">
      <div v-if="loading" style="text-align:center;padding:48px;color:#888;"><i class="fas fa-spinner fa-spin"></i> 載入中...</div>
      <p v-else-if="!items.length" style="text-align:center;padding:48px;color:#888;">尚無商品</p>
      <template v-else>
        <table>
          <thead>
            <tr>
              <th style="width:60px;">圖片</th>
              <th>商品名稱</th>
              <th style="text-align:center;">分類</th>
              <th style="text-align:center;">售價</th>
              <th style="text-align:center;">狀態</th>
              <th style="text-align:center;width:90px;">操作</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in items" :key="p.id" :style="p.status === 'inactive' ? 'opacity:0.6' : ''">
              <td>
                <img v-if="p.image" :src="p.image" style="width:50px;height:50px;object-fit:cover;border-radius:6px;" />
                <div v-else style="width:50px;height:50px;background:#eee;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#bbb;font-size:12px;">無</div>
              </td>
              <td style="font-weight:600;">{{ p.name }}</td>
              <td style="text-align:center;">
                <span v-if="p.category" class="tag">{{ p.category }}</span>
                <span v-else style="color:#bbb;">—</span>
              </td>
              <td style="text-align:center;">
                <span style="font-weight:600;">{{ fmt(p.price) }}</span>
              </td>
              <td style="text-align:center;">
                <span v-if="p.status === 'active'" class="badge badge-active">上架中</span>
                <span v-else class="badge badge-inactive">已下架</span>
              </td>
              <td style="text-align:center;">
                <router-link :to="`/products/${p.id}/edit`" style="color:#4CAF50;font-size:16px;" title="修改"><i class="fas fa-edit"></i></router-link>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="pagination" v-if="totalPages > 1">
          <span>共 {{ total }} 筆</span>
          <div class="btns">
            <button :disabled="page <= 1" @click="goPage(page - 1)">上一頁</button>
            <span>{{ page }} / {{ totalPages }}</span>
            <button :disabled="page >= totalPages" @click="goPage(page + 1)">下一頁</button>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'

export default {
  name: 'ProductsView',
  data() {
    return { items: [], categories: [], q: '', category: '', page: 1, totalPages: 1, total: 0, loading: true }
  },
  async created() {
    const cRes = await api.categories()
    if (cRes.success) this.categories = cRes.data || []
    await this.load()
  },
  methods: {
    fmt(n) {
      return 'NT$ ' + Number(n).toLocaleString()
    },
    async load() {
      this.loading = true
      const res = await api.products({ q: this.q, category: this.category, page: this.page })
      if (res.success) {
        this.items = res.data.items || []
        this.total = res.data.total
        this.totalPages = res.data.total_pages
      }
      this.loading = false
    },
    search() {
      this.page = 1
      this.load()
    },
    reset() {
      this.q = ''
      this.category = ''
      this.page = 1
      this.load()
    },
    goPage(p) {
      this.page = p
      this.load()
    },
  },
}
</script>

<style scoped>
.filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; padding: 16px 24px; }
.filter-bar input {
  padding: 10px 14px; border: 1px solid #d0d5dd; border-radius: 8px; font-size: 14px; outline: none; min-width: 240px; flex: 1;
}
.filter-bar input:focus, .filter-bar select:focus { border-color: #4CAF50; box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.12); }
.filter-bar select { padding: 10px 12px; border: 1px solid #d0d5dd; border-radius: 8px; font-size: 14px; outline: none; }
.tag { background: #f0f0f0; padding: 2px 10px; border-radius: 10px; font-size: 12px; color: #666; }
</style>
