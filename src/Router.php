<?php

class Router {
    private array $routes = [];
    private $notFoundHandler = null;  // Remove type hint since we need to store a callable

    /**
     * Register a new route
     * 
     * @param string $method HTTP method (GET, POST)
     * @param string $path URL path
     * @param callable $handler Route handler function
     */
    public function register(string $method, string $path, callable $handler): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Set 404 handler
     */
    public function setNotFoundHandler(callable $handler): void {
        $this->notFoundHandler = $handler;
    }

    /**
     * Match the current request to a route
     * 
     * @return array|null Matched route or null if not found
     */
    private function matchRoute(string $method, string $path): ?array {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            // Convert route parameters to regex pattern
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = "#^{$pattern}$#";

            if (preg_match($pattern, $path, $matches)) {
                // Filter out numeric keys from matches
                $params = array_filter($matches, function($key) {
                    return !is_numeric($key);
                }, ARRAY_FILTER_USE_KEY);

                return [
                    'handler' => $route['handler'],
                    'params' => $params
                ];
            }
        }

        return null;
    }

    /**
     * Handle the current request
     */
    public function handle(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $route = $this->matchRoute($method, $path);

        if ($route) {
            call_user_func_array($route['handler'], [$route['params']]);
            return;
        }

        if ($this->notFoundHandler) {
            call_user_func($this->notFoundHandler);
            return;
        }

        // Default 404 response
        header('HTTP/1.1 404 Not Found');
        echo '404 Not Found';
    }
}