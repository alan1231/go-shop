<?php /** @var int $totalProducts */ /** @var int $totalOrders */ /** @var int $totalUsers */ /** @var string $revenue */ /** @var array $recentOrders */ ?>
<div class="page-header">
    <h1><i class="fas fa-tachometer-alt"></i> 儀表板</h1>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:28px;">
    <a href="<?= BASE_URL ?>/admin/products" style="text-decoration:none;color:inherit;">
        <div class="card" style="text-align:center;padding:24px;transition:transform 0.15s,box-shadow 0.15s;cursor:pointer;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.12)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div style="font-size:32px;color:#4CAF50;"><i class="fas fa-box"></i></div>
            <div style="font-size:28px;font-weight:700;margin:8px 0;"><?= $totalProducts ?></div>
            <div style="color:#888;font-size:14px;">商品總數</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>/admin/orders" style="text-decoration:none;color:inherit;">
        <div class="card" style="text-align:center;padding:24px;transition:transform 0.15s,box-shadow 0.15s;cursor:pointer;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.12)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div style="font-size:32px;color:#2196F3;"><i class="fas fa-shopping-cart"></i></div>
            <div style="font-size:28px;font-weight:700;margin:8px 0;"><?= $totalOrders ?></div>
            <div style="color:#888;font-size:14px;">訂單總數</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>/admin/users" style="text-decoration:none;color:inherit;">
        <div class="card" style="text-align:center;padding:24px;transition:transform 0.15s,box-shadow 0.15s;cursor:pointer;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.12)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div style="font-size:32px;color:#FF9800;"><i class="fas fa-users"></i></div>
            <div style="font-size:28px;font-weight:700;margin:8px 0;"><?= $totalUsers ?></div>
            <div style="color:#888;font-size:14px;">會員總數</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>/admin/orders" style="text-decoration:none;color:inherit;">
        <div class="card" style="text-align:center;padding:24px;transition:transform 0.15s,box-shadow 0.15s;cursor:pointer;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.12)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div style="font-size:32px;color:#f44336;"><i class="fas fa-dollar-sign"></i></div>
            <div style="font-size:28px;font-weight:700;margin:8px 0;">NT$ <?= number_format((int)$revenue, 0) ?></div>
            <div style="color:#888;font-size:14px;">已完成訂單總額</div>
        </div>
    </a>
</div>

<div class="card">
    <h3 style="font-size:16px;margin-bottom:16px;">最近訂單</h3>
    <?php if (empty($recentOrders)): ?>
        <p style="color:#888;text-align:center;padding:24px;">目前無訂單</p>
    <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <thead>
                <tr style="background:#f8f9fa;text-align:left;">
                    <th style="padding:12px 16px;border-bottom:2px solid #eee;">訂單編號</th>
                    <th style="padding:12px 16px;border-bottom:2px solid #eee;">會員</th>
                    <th style="padding:12px 16px;border-bottom:2px solid #eee;">金額</th>
                    <th style="padding:12px 16px;border-bottom:2px solid #eee;">狀態</th>
                    <th style="padding:12px 16px;border-bottom:2px solid #eee;">日期</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $order): ?>
                    <tr style="border-bottom:1px solid #f0f0f0;">
                        <td style="padding:12px 16px;">#<?= $order['id'] ?></td>
                        <td style="padding:12px 16px;"><?= htmlspecialchars($order['username']) ?></td>
                        <td style="padding:12px 16px;font-weight:600;">NT$ <?= number_format($order['total_amount'], 0) ?></td>
                        <td style="padding:12px 16px;">
                            <span style="padding:4px 10px;border-radius:12px;font-size:12px;background:<?= match($order['status']) { 'completed' => '#e8f5e9', 'paid' => '#e3f2fd', 'shipped' => '#fff3e0', 'cancelled' => '#ffebee', default => '#f5f5f5' } ?>;color:<?= match($order['status']) { 'completed' => '#2e7d32', 'paid' => '#1565c0', 'shipped' => '#e65100', 'cancelled' => '#c62828', default => '#666' } ?>;">
                                <?= match($order['status']) { 'pending' => '待付款', 'paid' => '已付款', 'shipped' => '出貨中', 'completed' => '已完成', 'cancelled' => '已取消', default => $order['status'] } ?>
                            </span>
                        </td>
                        <td style="padding:12px 16px;color:#888;"><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
