<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-utensils"></i> 菜單列表</h1>
      <router-link to="/products/add" class="btn btn-primary"><i class="fas fa-plus"></i> 新增菜單</router-link>
    </div>

    <div class="card filter-bar">
      <input type="text" v-model="q" placeholder="搜尋菜單名稱或描述..." @keyup.enter="search" />
      <select v-model="category">
        <option value="">全部分類</option>
        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
      </select>
      <button class="btn btn-primary" @click="search"><i class="fas fa-search"></i> 篩選</button>
      <button v-if="q || category" class="btn btn-default" @click="reset">清除</button>
    </div>

    <div class="card category-sort" v-if="categories.length">
      <div class="category-sort-header">
        <h3><i class="fas fa-arrows-alt-h"></i> 分類排序</h3>
        <span>拖曳卡片即可調整前台菜單的分類顯示順序</span>
      </div>
      <div class="category-chips">
        <div
          v-for="(c, i) in categories"
          :key="c"
          class="category-chip"
          :class="{ 'dragging': dragIndex === i, 'drag-over': dragOverIndex === i }"
          draggable="true"
          @dragstart="onDragStart(i, $event)"
          @dragenter.prevent="dragOverIndex = i"
          @dragover.prevent="dragOverIndex = i"
          @dragend="onDragEnd"
          @drop.prevent="onDrop(i)"
        >
          <span class="category-chip-index">{{ i + 1 }}</span>
          <span class="category-chip-name">{{ c }}</span>
          <i class="fas fa-grip-vertical category-chip-grip"></i>
        </div>
      </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden;">
      <div v-if="loading" style="text-align:center;padding:48px;color:#888;"><i class="fas fa-spinner fa-spin"></i> 載入中...</div>
      <p v-else-if="!items.length" style="text-align:center;padding:48px;color:#888;">尚無菜單</p>
      <template v-else>
        <table>
          <thead>
            <tr>
              <th style="width:36px;"></th>
              <th style="width:60px;">圖片</th>
              <th>菜單名稱</th>
              <th style="text-align:center;">分類</th>
              <th style="text-align:center;">定價 / 特價</th>
              <th style="text-align:center;">狀態</th>
              <th style="text-align:center;width:90px;">操作</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="p in items"
              :key="p.id"
              class="draggable-row"
              :class="{ 'dragging': pDragId === p.id, 'drag-over': pOverId === p.id }"
              :style="p.status === 'inactive' ? 'opacity:0.6' : ''"
              draggable="true"
              @dragstart="onProductDragStart(p.id, $event)"
              @dragenter.prevent="pOverId = p.id"
              @dragover.prevent="pOverId = p.id"
              @dragend="onProductDragEnd"
              @drop.prevent="onProductDrop(p.id)"
            >
              <td class="drag-handle"><i class="fas fa-grip-vertical"></i></td>
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
                <span v-if="p.list_price && p.list_price > p.price" style="text-decoration:line-through;color:#aaa;font-size:12px;margin-right:4px;">{{ fmt(p.list_price) }}</span>
                <span :style="p.list_price && p.list_price > p.price ? 'color:#e44d26;font-weight:700;' : 'font-weight:600;'">{{ fmt(p.price) }}</span>
              </td>
              <td style="text-align:center;">
                <span v-if="p.status === 'active'" class="badge badge-active">上架中</span>
                <span v-else class="badge badge-inactive">已下架</span>
              </td>
              <td style="text-align:center;">
                <router-link :to="`/products/${p.id}/edit`" style="color:#4CAF50;font-size:16px;margin-right:10px;" title="修改"><i class="fas fa-edit"></i></router-link>
                <button type="button" style="color:#e53935;background:none;border:none;cursor:pointer;font-size:16px;" title="刪除" @click="remove(p)"><i class="fas fa-trash-alt"></i></button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="total" style="padding:12px 16px;color:#888;font-size:13px;">共 {{ total }} 筆（拖曳左側手柄可調整順序）</div>
      </template>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'

export default {
  name: 'ProductsView',
  data() {
    return { items: [], categories: [], q: '', category: '', total: 0, loading: true, dragIndex: null, dragOverIndex: null, pDragId: null, pOverId: null }
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
      const res = await api.products({ q: this.q, category: this.category, per_page: 10000, page: 1 })
      if (res.success) {
        this.items = res.data.items || []
        this.total = res.data.total
      }
      this.loading = false
    },
    search() {
      this.load()
    },
    reset() {
      this.q = ''
      this.category = ''
      this.load()
    },
    async remove(p) {
      if (!window.confirm(`確定要刪除「${p.name}」嗎？此操作無法復原。`)) return
      const res = await api.deleteProduct(p.id)
      if (res.success) {
        await this.load()
      } else {
        alert(res.message)
      }
    },
    onDragStart(i, e) {
      this.dragIndex = i
      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move'
        e.dataTransfer.setData('text/plain', String(i))
      }
    },
    onDragEnd() {
      this.dragIndex = null
      this.dragOverIndex = null
    },
    onDrop(i) {
      const from = this.dragIndex
      this.dragIndex = null
      this.dragOverIndex = null
      if (from === null || from === i) return
      const arr = this.categories.slice()
      const [moved] = arr.splice(from, 1)
      arr.splice(i, 0, moved)
      this.categories = arr
      this.saveCategoryOrder()
    },
    async saveCategoryOrder() {
      const res = await api.reorderCategories(this.categories)
      if (!res.success) {
        alert(res.message)
        await this.reloadCategories()
      }
    },
    async reloadCategories() {
      const cRes = await api.categories()
      if (cRes.success) this.categories = cRes.data || []
    },
    onProductDragStart(id, e) {
      this.pDragId = id
      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move'
        e.dataTransfer.setData('text/plain', String(id))
      }
    },
    onProductDragEnd() {
      this.pDragId = null
      this.pOverId = null
    },
    onProductDrop(id) {
      const from = this.pDragId
      this.pDragId = null
      this.pOverId = null
      if (from === null || from === id) return
      const arr = this.items.slice()
      const fromIdx = arr.findIndex((p) => p.id === from)
      const toIdx = arr.findIndex((p) => p.id === id)
      if (fromIdx < 0 || toIdx < 0) return
      const [moved] = arr.splice(fromIdx, 1)
      arr.splice(toIdx, 0, moved)
      this.items = arr
      this.saveProductOrder()
    },
    async saveProductOrder() {
      const res = await api.reorderProducts(this.items.map((p) => p.id))
      if (!res.success) {
        alert(res.message)
        await this.load()
      }
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
.category-sort { padding: 16px 24px; }
.category-sort-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.category-sort-header h3 { font-size: 16px; margin: 0; }
.category-sort-header span { font-size: 12px; color: #888; }
.category-chips { display: flex; flex-wrap: wrap; gap: 10px; }
.category-chip {
  display: flex; align-items: center; gap: 8px;
  background: #fafafa; border: 1px solid #e0e0e0; border-radius: 10px;
  padding: 8px 12px; cursor: grab; user-select: none;
  transition: box-shadow 0.15s, border-color 0.15s, opacity 0.15s;
}
.category-chip:hover { border-color: #4CAF50; }
.category-chip:active { cursor: grabbing; }
.category-chip.dragging { opacity: 0.4; }
.category-chip.drag-over { border-color: #4CAF50; box-shadow: 0 0 0 2px rgba(76,175,80,0.3); }
.category-chip-index { width: 22px; height: 22px; border-radius: 50%; background: #4CAF50; color: #fff; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.category-chip-name { font-weight: 600; }
.category-chip-grip { color: #bbb; font-size: 13px; }
.draggable-row { cursor: grab; }
.draggable-row:active { cursor: grabbing; }
.draggable-row.dragging { opacity: 0.4; }
.draggable-row.drag-over { box-shadow: inset 0 3px 0 #4CAF50; }
.drag-handle { color: #bbb; text-align: center; cursor: grab; width: 36px; }
.drag-handle:hover { color: #4CAF50; }
</style>
