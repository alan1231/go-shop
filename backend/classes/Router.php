<?php
// 簡易路由器：註冊路由 → 比對 URI → 執行 Handler
class Router {
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }

    // 註冊 GET 路由
    public function get(string $path, callable|array $handler): void {
        $this->routes[] = ['GET', $path, $handler];
    }

    // 註冊 POST 路由
    public function post(string $path, callable|array $handler): void {
        $this->routes[] = ['POST', $path, $handler];
    }

    // 比對當前請求，執行第一個符合的路由
    public function dispatch(string $method, string $uri): void {
        // 移除 query string 與 base path 前綴
        $uri = parse_url($uri, PHP_URL_PATH);
        if ($this->basePath && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            // 將 {id} 等佔位符轉為正則捕獲組
            $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $route[1]);
            $pattern = '#^' . $pattern . '$#';

            if ($route[0] === $method && preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                $handler = $route[2];
                if (is_array($handler)) {
                    // [ClassName, methodName]
                    [$class, $action] = $handler;
                    $controller = new $class();
                    $controller->$action(...$matches);
                } else {
                    // Closure
                    $handler(...$matches);
                }
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }
}