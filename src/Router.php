<?php
class Router {
    private array $routes = [];
    private $notFoundHandler = null;
    private ?Auth $auth = null;

    public function __construct(Auth $auth = null) {
        $this->auth = $auth;
    }

    /**
     * Register a protected route that requires authentication
     */
    public function registerProtected(string $method, string $path, callable $handler): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'protected' => true
        ];
    }

    /**
     * Register a public route (no auth required)
     */
    public function register(string $method, string $path, callable $handler): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'protected' => false
        ];
    }

    private function matchRoute(string $method, string $path): ?array {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = "#^{$pattern}$#";

            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, function($key) {
                    return !is_numeric($key);
                }, ARRAY_FILTER_USE_KEY);

                return [
                    'handler' => $route['handler'],
                    'params' => $params,
                    'protected' => $route['protected']
                ];
            }
        }

        return null;
    }

    /**
     * Set 404 handler
     */
    public function setNotFoundHandler(callable $handler): void {
        $this->notFoundHandler = $handler;
    }

    public function handle(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Check rate limit for all requests
        if ($this->auth && !$this->auth->checkRateLimit()) {
            http_response_code(429);
            echo 'Too Many Requests';
            return;
        }

        $route = $this->matchRoute($method, $path);

        if ($route) {
            // Check authentication for protected routes
            if ($route['protected'] && $this->auth) {
                if (!$this->auth->validateSession()) {
                    header('Location: /login');
                    exit;
                }
            }

            call_user_func_array($route['handler'], [$route['params']]);
            return;
        }

        if ($this->notFoundHandler) {
            call_user_func($this->notFoundHandler);
            return;
        }

        header('HTTP/1.1 404 Not Found');
        echo '404 Not Found';
    }
}