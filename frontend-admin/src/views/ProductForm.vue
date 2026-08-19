<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-box"></i> {{ isEdit ? '修改商品' : '新增商品' }}</h1>
      <router-link to="/products" class="btn btn-default"><i class="fas fa-arrow-left"></i> 返回列表</router-link>
    </div>

    <div v-if="msg" :class="'msg msg-' + msgType"><i class="fas fa-info-circle"></i> {{ msg }}</div>

    <div class="card" style="max-width:640px;">
      <form @submit.prevent="submit">
        <div class="form-group">
          <label>商品名稱 *</label>
          <input type="text" v-model="form.name" required placeholder="輸入商品名稱" />
        </div>
        <div class="form-group">
          <label>商品描述</label>
          <textarea v-model="form.description" placeholder="輸入商品描述"></textarea>
        </div>
        <div class="form-group">
          <label>分類</label>
          <input type="text" v-model="form.category" placeholder="例如：3C、服飾、生活用品" list="catList" />
          <datalist id="catList">
            <option v-for="c in categories" :key="c" :value="c"></option>
          </datalist>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div class="form-group">
            <label>售價 (NT$) *</label>
            <input type="number" v-model.number="form.price" required min="0" step="0.01" />
          </div>
          <div class="form-group">
            <label>定價 (NT$)</label>
            <input type="number" v-model.number="form.list_price" min="0" step="0.01" />
          </div>
        </div>
        <div class="form-group">
          <label>狀態</label>
          <select v-model="form.status">
            <option value="active">上架中</option>
            <option value="inactive">已下架</option>
          </select>
        </div>
        <div class="form-group">
          <label>商品圖片</label>
          <div style="display:flex;align-items:center;gap:16px;">
            <input type="file" accept="image/*" @change="onFile" />
            <img v-if="preview" :src="preview" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #eee;" />
          </div>
        </div>
        <button class="btn btn-primary" style="width:100%;" :disabled="loading">
          <i class="fas fa-save"></i> {{ loading ? '儲存中...' : '儲存' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import { api } from '../api/index.js'

export default {
  name: 'ProductFormView',
  data() {
    return {
      isEdit: !!this.$route.params.id,
      form: { name: '', description: '', category: '', price: 0, list_price: '', status: 'active' },
      file: null,
      preview: '',
      categories: [],
      msg: '',
      msgType: 'success',
      loading: false,
    }
  },
  async created() {
    const cRes = await api.categories()
    if (cRes.success) this.categories = cRes.data || []
    if (this.isEdit) {
      const res = await api.product(this.$route.params.id)
      if (res.success) {
        const p = res.data
        this.form = {
          name: p.name,
          description: p.description,
          category: p.category || '',
          price: p.price,
          list_price: p.list_price || '',
          status: p.status,
        }
        this.preview = p.image
      }
    }
  },
  methods: {
    onFile(e) {
      this.file = e.target.files[0] || null
      if (this.file) this.preview = URL.createObjectURL(this.file)
    },
    async submit() {
      this.loading = true
      this.msg = ''
      const fd = new FormData()
      fd.append('name', this.form.name)
      fd.append('description', this.form.description || '')
      fd.append('category', this.form.category || '')
      fd.append('price', this.form.price)
      fd.append('status', this.form.status)
      if (this.form.list_price !== '' && this.form.list_price != null) fd.append('list_price', this.form.list_price)
      if (this.file) fd.append('image', this.file)

      const res = this.isEdit
        ? await api.updateProduct(this.$route.params.id, fd)
        : await api.createProduct(fd)
      this.loading = false
      if (res.success) {
        this.msgType = 'success'
        this.msg = res.message
        if (!this.isEdit) {
          this.form = { name: '', description: '', category: '', price: 0, list_price: '', status: 'active' }
          this.file = null
          this.preview = ''
        }
      } else {
        this.msgType = 'error'
        this.msg = res.message
      }
    },
  },
}
</script>
