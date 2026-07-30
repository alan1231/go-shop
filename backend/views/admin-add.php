<?php /** @var string $message */ /** @var string $message_type */ ?>
<div class="page-header">
    <h1><i class="fas fa-plus-circle"></i> 新增商品</h1>
</div>

<div class="card" style="max-width:640px;">
    <?php $msg_type = $message_type ?? ''; ?>
    <?php if (!empty($message)): ?>
        <div class="msg msg-<?= $msg_type ?: 'error' ?>"><i class="fas fa-<?= $msg_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= $message ?></div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/admin/add" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>商品名稱</label>
            <input type="text" name="name" required placeholder="例如：iPhone 15">
        </div>
        <div class="form-group">
            <label>商品圖片</label>
            <input type="file" name="image" accept="image/*">
        </div>
        <div class="form-group">
            <label>商品描述</label>
            <textarea name="description" rows="4" placeholder="商品描述"></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label>定價</label>
                <input type="number" name="list_price" step="0.01" placeholder="原價（選填）">
            </div>
            <div class="form-group">
                <label>售價</label>
                <input type="number" name="price" step="0.01" required placeholder="例如：25900">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label>總庫存</label>
                <input type="number" name="stock" value="0" required>
            </div>
            <div class="form-group">
                <label>上架數量</label>
                <input type="number" name="listed_stock" value="0" required>
            </div>
        </div>
        <div class="form-group">
            <label>上架狀態</label>
            <select name="status" style="padding:10px 12px;border:1px solid #d0d5dd;border-radius:6px;font-size:14px;max-width:200px;">
                <option value="active">上架中</option>
                <option value="inactive">已下架</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">新增商品</button>
        <a href="<?= BASE_URL ?>/admin/products" class="btn btn-default" style="margin-left:10px;">取消</a>
    </form>
</div>
