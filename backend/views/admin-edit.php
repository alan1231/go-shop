<?php /** @var array $p */ ?>
<div class="page-header">
    <h1><i class="fas fa-edit"></i> 修改商品</h1>
</div>

<div class="card" style="max-width:640px;">
    <?php $msg_type = $message_type ?? ''; ?>
    <?php if (!empty($message)): ?>
        <div class="msg msg-<?= $msg_type ?: 'error' ?>"><i class="fas fa-<?= $msg_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= $message ?></div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/admin/edit/<?= $p['id'] ?>" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>商品名稱</label>
            <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" required>
        </div>
        <div class="form-group">
            <label>商品分類</label>
            <input type="text" name="category" value="<?= htmlspecialchars($p['category'] ?? '') ?>" placeholder="例如：手機、水果、服飾">
        </div>
        <div class="form-group">
            <label>目前圖片</label>
            <div>
                <?php if ($p['image']): ?>
                    <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($p['image']) ?>" alt="" style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #eee;">
                <?php else: ?>
                    <div style="width:120px;height:120px;background:#f5f5f5;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#bbb;font-size:13px;">無圖片</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-group">
            <label>更換圖片</label>
            <input type="file" name="image" accept="image/*">
            <p style="font-size:12px;color:#888;margin-top:4px;">留則不更換</p>
        </div>
        <div class="form-group">
            <label>商品描述</label>
            <textarea name="description" rows="4"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label>定價</label>
                <input type="number" name="list_price" step="0.01" placeholder="原價" value="<?= $p['list_price'] ?>">
            </div>
            <div class="form-group">
                <label>售價</label>
                <input type="number" name="price" step="0.01" value="<?= $p['price'] ?>" required>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label>總庫存</label>
                <input type="number" name="stock" value="<?= (int)$p['stock'] ?>" required>
            </div>
            <div class="form-group">
                <label>上架數量</label>
                <input type="number" name="listed_stock" value="<?= (int)$p['listed_stock'] ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label>上架狀態</label>
            <select name="status" style="padding:10px 12px;border:1px solid #d0d5dd;border-radius:6px;font-size:14px;max-width:200px;">
                <option value="active" <?= $p['status'] === 'active' ? 'selected' : '' ?>>上架中</option>
                <option value="inactive" <?= $p['status'] === 'inactive' ? 'selected' : '' ?>>已下架</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">儲存修改</button>
        <a href="<?= BASE_URL ?>/admin/products" class="btn btn-default" style="margin-left:10px;">取消</a>
    </form>
</div>
