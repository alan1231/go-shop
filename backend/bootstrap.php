<?php

require __DIR__ . '/vendor/autoload.php';

use App\Config;
use App\Database;
use App\Images;
use App\Migrate;
use App\Registry;
use App\Repositories\AdminUserRepository;
use App\Repositories\MarqueeRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\UserRepository;
use App\Services\AdminAccountService;
use App\Services\AuthService;
use App\Services\DashboardService;
use App\Services\MarqueeService;
use App\Services\OrderService;
use App\Services\PrintService;
use App\Services\ProductService;
use App\Services\SettingsService;
use LinePay\LinePayClient;
use LinePay\LinePayConfig;
use LinePay\LinePayGateway;

Config::load(__DIR__ . '/../.env');
$pdo = Database::connect();
(new Migrate($pdo))->run();

Registry::set('pdo', $pdo);
Registry::set('userRepo', new UserRepository());
Registry::set('adminRepo', new AdminUserRepository());
Registry::set('productRepo', new ProductRepository());
Registry::set('orderRepo', new OrderRepository());
Registry::set('marqueeRepo', new MarqueeRepository());
Registry::set('settingsRepo', new SettingsRepository());

Registry::set('images', new Images(Config::get('UPLOADS_DIR')));
Registry::set('authSvc', new AuthService(Registry::get('adminRepo')));
Registry::set('adminAccountSvc', new AdminAccountService(Registry::get('adminRepo')));
Registry::set('productSvc', new ProductService(Registry::get('productRepo'), Registry::get('images')));
Registry::set('settingsSvc', new SettingsService(Registry::get('settingsRepo')));
$lp = Registry::get('settingsSvc')->getLinePay();
Registry::set('linePayGateway', new LinePayGateway(new LinePayClient(new LinePayConfig(
    $lp['channel_id'],
    $lp['channel_secret'],
    $lp['sandbox'] === '1'
))));
Registry::set('printSvc', new PrintService());
Registry::set('orderSvc', new OrderService($pdo, Registry::get('orderRepo'), Registry::get('productRepo'), Registry::get('linePayGateway'), Registry::get('printSvc')));
Registry::set('marqueeSvc', new MarqueeService(Registry::get('marqueeRepo')));
Registry::set('dashboardSvc', new DashboardService(Registry::get('productRepo'), Registry::get('orderRepo'), Registry::get('userRepo')));