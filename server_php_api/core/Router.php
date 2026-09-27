<?php

class Router
{

    private array $routes = [];

    public function __construct()
    {

        $this->add('GET',    '/api/products',            fn () => (new ProductController())->index());
        $this->add('GET',    '/api/products/{id}',       fn ($p) => (new ProductController())->show((int)$p['id']));
        $this->add('POST',   '/api/admin/login',         fn () => (new AdminAuthController())->login());
        $this->add('POST',   '/api/admin/logout',        fn () => (new AdminAuthController())->logout());
        $this->add('POST',   '/api/admin/products',      fn () => (new ProductController())->store());
        $this->add('PUT',    '/api/admin/products/{id}', fn ($p) => (new ProductController())->update((int)$p['id']));
        $this->add('DELETE', '/api/admin/products/{id}', fn ($p) => (new ProductController())->destroy((int)$p['id']));
    }

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[$method][] = [$pattern, $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri  = rtrim(rawurldecode($uri), '/') ?: '/';
        $best = null; 

        foreach ($this->routes[$method] ?? [] as [$pattern, $handler]) {
            $regex = preg_quote($pattern, '#');

            $regex = preg_replace('#\\\\\{(\w+)\\\\\}#', '(?P<$1>[^/]+)', $regex);
            if (preg_match('#^' . $regex . '$#iu', $uri, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                $handler($params);
                return;
            }
        }

        Response::json(['detail' => 'Не найдено: ' . $uri], 404);
    }
}
