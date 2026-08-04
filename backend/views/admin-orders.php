<?php /** @var array $orders */
$statusMap = [
    'pending'   => ['label' => '待付款', 'color' => '#f5f5f5', 'text' => '#666'],
    'paid'      => ['label' => '已付款', 'color' => '#e3f2fd', 'text' => '#1565c0'],
    'shipped'   => ['label' => '出貨中', 'color' => '#fff3e0', 'text' => '#e65100'],
    'completed' => ['label' => '已完成', 'color' => '#e8f5e9', 'text' => '#2e7d32'],
    'cancelled' => ['label' => '已取消', 'color' => '#ffebee', 'text' => '#c62828'],
];
$currentStatus = $_GET['status'] ?? '';
?>
<div class="page-header">
    <h1><i class="fas fa-shopping-cart"></i> 訂單管理</h1>
</div>

<div class="card" style="padding:16px 24px;margin-bottom:20px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <span style="font-weight:600;font-size:14px;margin-right:8px;">篩選：</span>
    <a href="<?= BASE_URL ?>/admin/orders" style="padding:6px 14px;border-radius:20px;font-size:13px;text-decoration:none;<?= !$currentStatus ? 'background:#4CAF50;color:#fff' : 'background:#f0f0f0;color:#666' ?>">全部</a>
    <?php foreach ($statusMap as $key => $s): ?>
        <a href="<?= BASE_URL ?>/admin/orders?status=<?= $key ?>" style="padding:6px 14px;border-radius:20px;font-size:13px;text-decoration:none;<?= $currentStatus === $key ? 'background:' . $s['text'] . ';color:#fff' : 'background:#f0f0f0;color:#666' ?>"><?= $s['label'] ?></a>
    <?php endforeach; ?>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <?php if (empty($orders)): ?>
        <p style="text-align:center;padding:48px;color:#888;">尚無訂單</p>
    <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:14px;vertical-align:middle;">
            <thead>
                <tr style="background:#f8f9fa;text-align:left;">
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;">訂單編號</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;">會員</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;">金額</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;">狀態</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;">日期</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;text-align:center;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <?php $s = $statusMap[$order['status']] ?? ['label' => $order['status'], 'color' => '#f5f5f5', 'text' => '#666']; ?>
                    <tr style="border-bottom:1px solid #f0f0f0;">
                        <td style="padding:12px 18px;font-weight:600;">#<?= $order['id'] ?></td>
                        <td style="padding:12px 18px;"><?= htmlspecialchars($order['username']) ?></td>
                        <td style="padding:12px 18px;font-weight:600;">NT$ <?= number_format($order['total_amount'], 0) ?></td>
                        <td style="padding:12px 18px;">
                            <span style="padding:4px 10px;border-radius:12px;font-size:12px;background:<?= $s['color'] ?>;color:<?= $s['text'] ?>;"><?= $s['label'] ?></span>
                        </td>
                        <td style="padding:12px 18px;color:#888;font-size:13px;"><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                        <td style="padding:12px 18px;text-align:center;">
                            <a href="<?= BASE_URL ?>/admin/orders/<?= $order['id'] ?><?= $currentStatus ? '?status=' . urlencode($currentStatus) : '' ?>" style="color:#4CAF50;text-decoration:none;font-size:16px;" title="檢視"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
