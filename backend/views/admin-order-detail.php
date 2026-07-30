<?php /** @var array $order */ /** @var array $orderItems */
$statusLabels = [
    'pending'   => '待付款',
    'paid'      => '已付款',
    'shipped'   => '出貨中',
    'completed' => '已完成',
    'cancelled' => '已取消',
];
?>
<div class="page-header">
    <h1><i class="fas fa-file-invoice"></i> 訂單 #<?= $order['id'] ?></h1>
    <a href="<?= BASE_URL ?>/admin/orders" class="btn btn-default"><i class="fas fa-arrow-left"></i> 回訂單列表</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
    <div class="card">
        <h3 style="font-size:15px;margin-bottom:12px;">訂單資訊</h3>
        <table style="font-size:14px;width:100%;">
            <tr><td style="padding:6px 0;color:#888;">訂單編號</td><td style="padding:6px 0;font-weight:600;">#<?= $order['id'] ?></td></tr>
            <tr><td style="padding:6px 0;color:#888;">會員</td><td style="padding:6px 0;"><?= htmlspecialchars($order['username']) ?></td></tr>
            <tr><td style="padding:6px 0;color:#888;">總金額</td><td style="padding:6px 0;font-weight:700;font-size:18px;color:#e44d26;">NT$ <?= number_format($order['total_amount'], 0) ?></td></tr>
            <tr><td style="padding:6px 0;color:#888;">建立時間</td><td style="padding:6px 0;"><?= $order['created_at'] ?></td></tr>
        </table>
    </div>
    <div class="card">
        <h3 style="font-size:15px;margin-bottom:12px;">更新狀態</h3>
        <form method="POST" action="<?= BASE_URL ?>/admin/orders/<?= $order['id'] ?>/status">
            <select name="status" style="padding:8px 12px;border:1px solid #d0d5dd;border-radius:6px;font-size:14px;margin-bottom:12px;width:100%;max-width:200px;">
                <?php foreach ($statusLabels as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $order['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary" style="padding:8px 20px;font-size:13px;">更新狀態</button>
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
