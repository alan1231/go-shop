<?php /** @var array $products */ ?>
<div class="page-header">
    <h1><i class="fas fa-box"></i> 商品列表</h1>
    <div>
        <a href="<?= BASE_URL ?>/admin" class="btn btn-default"><i class="fas fa-tachometer-alt"></i> 儀表板</a>
        <a href="<?= BASE_URL ?>/admin/add" class="btn btn-primary"><i class="fas fa-plus"></i> 新增商品</a>
    </div>
</div>

<?php if (empty($products)): ?>
    <div class="card" style="text-align:center;padding:60px 20px;color:#888;">
        <i class="fas fa-box-open" style="font-size:48px;margin-bottom:16px;color:#ccc;"></i>
        <p style="font-size:16px;margin-bottom:12px;">目前沒有任何商品</p>
        <a href="<?= BASE_URL ?>/admin/add" class="btn btn-primary"><i class="fas fa-plus"></i> 新增第一件商品</a>
    </div>
<?php else: ?>
    <div class="card" style="padding:0;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:14px;vertical-align:middle;">
            <thead>
                <tr style="background:#f8f9fa;text-align:left;">
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;width:60px;">圖片</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;">商品名稱</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;">分類</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;">定價 / 售價</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;text-align:center;">庫存 / 上架</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;">狀態</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;width:100px;text-align:center;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <?php $hasDiscount = $p['list_price'] && $p['list_price'] > $p['price']; ?>
                    <tr style="border-bottom:1px solid #f0f0f0;<?= $p['status'] === 'inactive' ? 'opacity:0.6' : '' ?>">
                        <td style="padding:12px 18px;">
                            <?php if ($p['image']): ?>
                                <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($p['image']) ?>" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:6px;">
                            <?php else: ?>
                                <div style="width:50px;height:50px;background:#eee;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#bbb;font-size:12px;">無</div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:12px 18px;font-weight:600;"><?= htmlspecialchars($p['name']) ?></td>
                        <td style="padding:12px 18px;"><?= $p['category'] ? '<span style="background:#f0f0f0;padding:2px 8px;border-radius:10px;font-size:12px;color:#666;">' . htmlspecialchars($p['category']) . '</span>' : '<span style="color:#bbb;">—</span>' ?></td>
                        <td style="padding:12px 18px;">
                            <?php if ($hasDiscount): ?>
                                <span style="text-decoration:line-through;color:#aaa;font-size:12px;">NT$ <?= number_format($p['list_price'], 0) ?></span><br>
                                <span style="color:#e44d26;font-weight:600;">NT$ <?= number_format($p['price'], 0) ?></span>
                            <?php else: ?>
                                <span style="font-weight:600;">NT$ <?= number_format($p['price'], 0) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:12px 18px;vertical-align:middle;">
                            <div style="text-align: center;"><?= (int)$p['stock'] ?> / <?= (int)$p['listed_stock'] ?></div>
                            <?php if ($p['status'] === 'active' && (int)$p['listed_stock'] === 0): ?>
                                <span style="padding:2px 6px;border-radius:8px;font-size:10px;background:#ffebee;color:#c62828;">完售</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:12px 18px;">
                            <?php if ($p['status'] === 'active'): ?>
                                <span style="padding:3px 8px;border-radius:10px;font-size:11px;background:#e8f5e9;color:#2e7d32;">上架中</span>
                            <?php else: ?>
                                <span style="padding:3px 8px;border-radius:10px;font-size:11px;background:#ffebee;color:#c62828;">已下架</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:12px 18px;text-align:center;">
                            <a href="<?= BASE_URL ?>/admin/edit/<?= $p['id'] ?>" style="color:#4CAF50;text-decoration:none;font-size:16px;" title="修改"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
