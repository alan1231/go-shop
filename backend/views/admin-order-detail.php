<?php /** @var array $order */ /** @var array $orderItems */
$statusLabels = [
    'pending'   => '待付款',
    'paid'      => '已付款',
    'shipped'   => '出貨中',
    'completed' => '已完成',
    'cancelled' => '已取消',
];
$backStatus = $_GET['status'] ?? '';
$backUrl = BASE_URL . '/admin/orders' . ($backStatus ? '?status=' . urlencode($backStatus) : '');
?>
<?php if (isset($_GET['updated'])): ?>
    <div style="background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;padding:12px 16px;border-radius:6px;margin-bottom:20px;font-size:14px;">
        <i class="fas fa-check-circle"></i> 訂單狀態已更新！
    </div>
<?php elseif (isset($_GET['remark_updated'])): ?>
    <div style="background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;padding:12px 16px;border-radius:6px;margin-bottom:20px;font-size:14px;">
        <i class="fas fa-check-circle"></i> 備註已更新！
    </div>
<?php endif; ?>
<div class="page-header">
    <h1><i class="fas fa-file-invoice"></i> 訂單 #<?= $order['id'] ?></h1>
    <a href="<?= $backUrl ?>" class="btn btn-default"><i class="fas fa-arrow-left"></i> 回訂單列表</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
    <div class="card">
        <h3 style="font-size:15px;margin-bottom:12px;">訂單資訊</h3>
        <table style="font-size:14px;width:100%;">
            <tr><td style="padding:6px 0;color:#888;">訂單編號</td><td style="padding:6px 0;font-weight:600;">#<?= $order['id'] ?></td></tr>
            <tr><td style="padding:6px 0;color:#888;">會員名稱</td><td style="padding:6px 0;"><?= htmlspecialchars($order['username']) ?></td></tr>
            <tr><td style="padding:6px 0;color:#888;">收件人</td><td style="padding:6px 0;"><?= htmlspecialchars($order['receiver_name'] ?? '未提供') ?></td></tr>
            <tr><td style="padding:6px 0;color:#888;">收件手機</td><td style="padding:6px 0;"><?= htmlspecialchars($order['receiver_phone'] ?? '未提供') ?></td></tr>
            <tr><td style="padding:6px 0;color:#888;">收件住址</td><td style="padding:6px 0;"><?= htmlspecialchars($order['receiver_address'] ?? '未提供') ?></td></tr>
            <?php if (!empty($order['member_remark'])): ?>
                <tr><td style="padding:6px 0;color:#888;vertical-align:top;">會員備註</td><td style="padding:6px 0;color:#555;white-space:pre-wrap;line-height:1.6;"><?= htmlspecialchars($order['member_remark']) ?></td></tr>
            <?php endif; ?>
            <tr><td style="padding:6px 0;color:#888;">總金額</td><td style="padding:6px 0;font-weight:700;font-size:18px;color:#e44d26;">NT$ <?= number_format($order['total_amount'], 0) ?></td></tr>
            <tr><td style="padding:6px 0;color:#888;">建立時間</td><td style="padding:6px 0;"><?= $order['created_at'] ?></td></tr>
        </table>
    </div>
    <div class="card">
        <h3 style="font-size:15px;margin-bottom:12px;">更新狀態</h3>
        <?php if ($order['status'] === 'completed'): ?>
            <p style="color:#999;font-size:14px;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-lock"></i> 訂單已完成，狀態不可再變更
            </p>
        <?php else: ?>
        <form method="POST" action="<?= BASE_URL ?>/admin/orders/<?= $order['id'] ?>/status" style="margin-bottom:18px;padding-bottom:18px;border-bottom:1px solid #f0f0f0;">
            <input type="hidden" name="back_status" value="<?= htmlspecialchars($backStatus) ?>">
            <select name="status" style="padding:8px 12px;border:1px solid #d0d5dd;border-radius:6px;font-size:14px;margin-bottom:12px;width:100%;max-width:200px;<?= $order['status'] === 'completed' ? 'background:#eee;color:#999;cursor:not-allowed;' : '' ?>" <?= $order['status'] === 'completed' ? 'disabled' : '' ?>>
                <?php foreach ($statusLabels as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $order['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary" style="padding:8px 20px;font-size:13px;<?= $order['status'] === 'completed' ? 'background:#ccc;cursor:not-allowed;' : '' ?>" <?= $order['status'] === 'completed' ? 'disabled' : '' ?>>更新狀態</button>
        </form>
        <?php endif; ?>

        <h3 style="font-size:15px;margin-bottom:12px;">備註</h3>
        <form method="POST" action="<?= BASE_URL ?>/admin/orders/<?= $order['id'] ?>/remark">
            <input type="hidden" name="back_status" value="<?= htmlspecialchars($backStatus) ?>">
            <textarea name="remark" placeholder="輸入備註（例如出貨注意事項、內部說明）" style="width:100%;padding:10px 12px;border:1px solid #d0d5dd;border-radius:6px;font-size:14px;resize:vertical;min-height:80px;outline:none;font-family:inherit;box-sizing:border-box;"><?= htmlspecialchars($order['remark'] ?? '') ?></textarea>
            <button type="submit" class="btn btn-primary" style="padding:8px 20px;font-size:13px;margin-top:12px;"><i class="fas fa-save"></i> 儲存備註</button>
        </form>
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <h3 style="font-size:15px;padding:18px 24px;border-bottom:1px solid #eee;">訂單明細</h3>
    <table style="width:100%;border-collapse:collapse;font-size:14px;vertical-align:middle;">
        <thead>
            <tr style="background:#f8f9fa;text-align:left;">
                <th style="padding:12px 18px;border-bottom:2px solid #eee;width:60px;">圖片</th>
                <th style="padding:12px 18px;border-bottom:2px solid #eee;">商品</th>
                <th style="padding:12px 18px;border-bottom:2px solid #eee;">單價</th>
                <th style="padding:12px 18px;border-bottom:2px solid #eee;">數量</th>
                <th style="padding:12px 18px;border-bottom:2px solid #eee;">小計</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderItems as $item): ?>
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:12px 18px;">
                        <?php if ($item['image']): ?>
                            <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($item['image']) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                        <?php else: ?>
                            <div style="width:48px;height:48px;background:#eee;border-radius:6px;"></div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px 18px;font-weight:600;"><?= htmlspecialchars($item['name']) ?></td>
                    <td style="padding:12px 18px;">NT$ <?= number_format($item['price'], 0) ?></td>
                    <td style="padding:12px 18px;"><?= (int)$item['quantity'] ?></td>
                    <td style="padding:12px 18px;font-weight:600;">NT$ <?= number_format($item['price'] * $item['quantity'], 0) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="padding:14px 18px;text-align:right;font-weight:600;">總計</td>
                <td style="padding:14px 18px;font-weight:700;font-size:16px;color:#e44d26;">NT$ <?= number_format($order['total_amount'], 0) ?></td>
            </tr>
        </tfoot>
    </table>
</div>
