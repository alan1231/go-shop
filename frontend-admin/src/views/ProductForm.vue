<template>
  <div>
    <div class="page-header">
      <h1><i class="fas fa-utensils"></i> {{ isEdit ? '修改菜單' : '新增菜單' }}</h1>
      <router-link to="/products" class="btn btn-default"><i class="fas fa-arrow-left"></i> 返回列表</router-link>
    </div>

    <div v-if="msg" :class="'msg msg-' + msgType"><i class="fas fa-info-circle"></i> {{ msg }}</div>

    <div class="card" style="max-width:640px;">
      <form @submit.prevent="submit">
        <div class="form-group">
          <label>菜單名稱 *</label>
          <input type="text" v-model="form.name" required placeholder="輸入菜單名稱" />
        </div>
        <div class="form-group">
          <label>菜單描述</label>
          <textarea v-model="form.description" placeholder="輸入菜單描述"></textarea>
        </div>
        <div class="form-group">
          <label>分類</label>
          <input type="text" v-model="form.category" placeholder="例如：主餐、甜點、飲料" list="catList" />
          <datalist id="catList">
            <option v-for="c in categories" :key="c" :value="c"></option>
          </datalist>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div class="form-group">
            <label>特價 (NT$) *</label>
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
          <label>菜單圖片</label>
          <div
            class="dropzone"
            :class="{ 'drag-over': dragOver }"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="onDrop"
            @click="$refs.fileInput.click()"
          >
            <i class="fas fa-cloud-upload-alt"></i>
            <span>拖曳圖片到此處，或點擊選擇</span>
            <input ref="fileInput" type="file" accept="image/*" style="display:none;" @change="onFile" />
          </div>
          <div v-if="preview" class="preview-wrap">
            <img :src="preview" class="img-preview" />
            <button v-if="file" type="button" class="btn btn-default img-clear" @click="clearImage">
              <i class="fas fa-times"></i> 取消圖片
            </button>
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
      originalPreview: '',
      dragOver: false,
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
        this.originalPreview = p.image
      }
    }
  },
  methods: {
    onFile(e) {
      this.file = e.target.files[0] || null
      if (this.file) this.preview = URL.createObjectURL(this.file)
    },
    onDrop(e) {
      this.dragOver = false
      const f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]
      if (!f) return
      if (!f.type || !f.type.startsWith('image/')) {
        this.msgType = 'error'
        this.msg = '請選擇圖片檔案'
        return
      }
      this.file = f
      this.preview = URL.createObjectURL(f)
    },
    clearImage() {
      this.file = null
      this.preview = this.originalPreview
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
          this.$router.push('/products')
          return
        }
      } else {
        this.msgType = 'error'
        this.msg = res.message
      }
    },
  },
}
</script>

<style scoped>
.dropzone {
  border: 2px dashed #d0d5dd; border-radius: 10px; padding: 22px; text-align: center;
  color: #999; cursor: pointer; background: #fafafa; transition: all 0.15s; user-select: none;
}
.dropzone:hover { border-color: #4CAF50; color: #4CAF50; }
.dropzone.drag-over { border-color: #4CAF50; background: #f1f8f1; color: #4CAF50; }
.dropzone i { font-size: 28px; display: block; margin-bottom: 6px; }
.img-preview { width: 96px; height: 96px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; }
.preview-wrap { margin-top: 12px; display: flex; align-items: center; gap: 12px; }
.img-clear { padding: 6px 14px; font-size: 13px; }
</style>
