<?php /** @var int $totalProducts */ /** @var int $totalOrders */ /** @var int $totalUsers */ /** @var string $revenue */ /** @var array $recentOrders */ /** @var array $statusCounts */ /** @var array $dailyStats */ /** @var array $topProducts */
$statusLabels = [
    'pending'   => ['label' => '待付款', 'color' => '#9e9e9e'],
    'paid'      => ['label' => '已付款', 'color' => '#2196F3'],
    'shipped'   => ['label' => '出貨中', 'color' => '#FF9800'],
    'completed' => ['label' => '已完成', 'color' => '#4CAF50'],
    'cancelled' => ['label' => '已取消', 'color' => '#f44336'],
];
$dailyLabels = [];
$dailyOrders = [];
$dailyRevenue = [];
foreach ($dailyStats as $d) {
    $dailyLabels[] = $d['day'];
    $dailyOrders[] = $d['orders'];
    $dailyRevenue[] = $d['revenue'];
}
?>
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

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
    <div class="card">
        <h3 style="font-size:16px;margin-bottom:16px;">訂單狀態分布</h3>
        <div style="height:260px;"><canvas id="statusChart"></canvas></div>
    </div>
    <div class="card">
        <h3 style="font-size:16px;margin-bottom:16px;">近 7 天訂單趨勢</h3>
        <div style="height:260px;"><canvas id="trendChart"></canvas></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
    <div class="card">
        <h3 style="font-size:16px;margin-bottom:16px;">熱銷商品 Top 5</h3>
        <div style="height:260px;"><canvas id="topChart"></canvas></div>
    </div>
    <div class="card">
        <h3 style="font-size:16px;margin-bottom:16px;">每日營收（NT$）</h3>
        <div style="height:260px;"><canvas id="revenueChart"></canvas></div>
    </div>
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
                        <td style="padding:12px 16px;"><a href="<?= BASE_URL ?>/admin/orders/<?= $order['id'] ?>" style="color:#4CAF50;text-decoration:none;font-weight:600;">#<?= $order['id'] ?></a></td>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const statusLabels = <?= json_encode(array_values(array_map(fn($s) => $s['label'], $statusLabels))) ?>;
    const statusColors = <?= json_encode(array_values(array_map(fn($s) => $s['color'], $statusLabels))) ?>;
    const statusCounts = <?= json_encode($statusCounts) ?>;
    const dailyLabels = <?= json_encode($dailyLabels) ?>;
    const dailyOrders = <?= json_encode($dailyOrders) ?>;
    const dailyRevenue = <?= json_encode($dailyRevenue) ?>;
    const topNames = <?= json_encode(array_column($topProducts, 'name')) ?>;
    const topSold = <?= json_encode(array_map('intval', array_column($topProducts, 'sold'))) ?>;

    const font = { family: "'Segoe UI', Arial, sans-serif" };
    const defaultOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { font } },
            tooltip: { titleFont: font, bodyFont: font },
        },
    };

    if (document.getElementById('statusChart')) {
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusLabels.map((_, i) => statusCounts[['pending', 'paid', 'shipped', 'completed', 'cancelled'][i]]),
                    backgroundColor: statusColors,
                    borderWidth: 2,
                    borderColor: '#fff',
                }],
            },
            options: { ...defaultOpts, plugins: { ...defaultOpts.plugins, legend: { position: 'right', labels: { font, boxWidth: 14 } } } },
        });
    }

    if (document.getElementById('trendChart')) {
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: dailyLabels,
                datasets: [{
                    label: '訂單數',
                    data: dailyOrders,
                    borderColor: '#2196F3',
                    backgroundColor: 'rgba(33,150,243,0.12)',
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#2196F3',
                }],
            },
            options: defaultOpts,
        });
    }

    if (document.getElementById('revenueChart')) {
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: dailyLabels,
                datasets: [{
                    label: '營收',
                    data: dailyRevenue,
                    backgroundColor: '#4CAF50',
                    borderRadius: 4,
                }],
            },
            options: {
                ...defaultOpts,
                scales: {
                    y: { beginAtZero: true, ticks: { font, callback: v => 'NT$' + v.toLocaleString() } },
                    x: { ticks: { font } },
                },
            },
        });
    }

    if (document.getElementById('topChart')) {
        new Chart(document.getElementById('topChart'), {
            type: 'bar',
            data: {
                labels: topNames,
                datasets: [{
                    label: '銷售數量',
                    data: topSold,
                    backgroundColor: ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#e44d26'],
                    borderRadius: 4,
                }],
            },
            options: {
                ...defaultOpts,
                indexAxis: 'y',
                plugins: { ...defaultOpts.plugins, legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { font } },
                    y: { ticks: { font } },
                },
            },
        });
    }
})();
</script>
