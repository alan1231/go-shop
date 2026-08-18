<?php

spl_autoload_register(function (string $class): void {
    $name = str_replace('\\', '/', $class);
    if (str_ends_with($name, 'Repository')) {
        $file = __DIR__ . '/classes/Repositories/' . $name . '.php';
    } elseif (str_ends_with($name, 'Service')) {
        $file = __DIR__ . '/classes/Services/' . $name . '.php';
    } elseif (str_ends_with($name, 'Controller')) {
        $file = __DIR__ . '/classes/Controllers/' . $name . '.php';
    } else {
        $file = __DIR__ . '/classes/' . $name . '.php';
    }
    if (file_exists($file)) {
        require $file;
    }
});

Config::load(__DIR__ . '/../.env');
$pdo = Database::connect();
(new Migrate($pdo))->run();

Registry::set('pdo', $pdo);
Registry::set('userRepo', new UserRepository());
Registry::set('adminRepo', new AdminUserRepository());
Registry::set('productRepo', new ProductRepository());
Registry::set('orderRepo', new OrderRepository());
Registry::set('cartRepo', new CartRepository());
Registry::set('marqueeRepo', new MarqueeRepository());
Registry::set('loginAttemptRepo', new LoginAttemptRepository());

Registry::set('images', new Images(Config::get('UPLOADS_DIR')));
Registry::set('authSvc', new AuthService(Registry::get('adminRepo')));
Registry::set('userSvc', new UserService(Registry::get('userRepo')));
Registry::set('productSvc', new ProductService(Registry::get('productRepo'), Registry::get('images')));
Registry::set('linePaySvc', new LinePayService(
    Config::get('LINE_PAY_CHANNEL_ID'),
    Config::get('LINE_PAY_CHANNEL_SECRET'),
    Config::get('LINE_PAY_SANDBOX', 'true')
));
Registry::set('orderSvc', new OrderService($pdo, Registry::get('orderRepo'), Registry::get('productRepo'), Registry::get('linePaySvc')));
Registry::set('cartSvc', new CartService(Registry::get('cartRepo'), Registry::get('productRepo')));
Registry::set('marqueeSvc', new MarqueeService(Registry::get('marqueeRepo')));
Registry::set('dashboardSvc', new DashboardService(Registry::get('productRepo'), Registry::get('orderRepo'), Registry::get('userRepo')));
Registry::set('rateLimitSvc', new RateLimitService(Registry::get('loginAttemptRepo')));
Registry::set('oauthSvc', new OAuthService(
    Config::get('GOOGLE_CLIENT_ID'),
    Config::get('GOOGLE_CLIENT_SECRET'),
    Config::get('LINE_CHANNEL_ID'),
    Config::get('LINE_CHANNEL_SECRET'),
    Config::get('OAUTH_REDIRECT_URI', 'http://localhost:5173/auth/callback')
));
