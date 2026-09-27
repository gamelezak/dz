<?php

class Router
{

    private array $routes = [];

    public function __construct()
    {

        $this->add('GET', '/',                 fn ($p) => (new PageController())->catalog());
        $this->add('GET', '/index.html',       fn ($p) => (new PageController())->catalog());
        $this->add('GET', '/product/{id}',     fn ($p) => (new PageController())->product((int)$p['id']));
        $this->add('GET', '/cart',             fn () => (new PageController())->cart());
        $this->add('GET', '/cart.html',        fn () => (new PageController())->cart());

        $U = AuthController::class;
        $this->add('GET',  '/login',      fn () => (new $U())->showLogin());
        $this->add('POST', '/login',      fn () => (new $U())->login());
        $this->add('GET',  '/register',   fn () => (new $U())->showRegister());
        $this->add('POST', '/register',   fn () => (new $U())->register());
        $this->add('GET',  '/account',    fn () => (new AuthController())->account());
        $this->add('GET',  '/logout',     fn () => (new AuthController())->logout());

        $O = OrderController::class;
        $this->add('GET',  '/checkout',          fn () => (new $O())->showCheckout());
        $this->add('POST', '/checkout',          fn () => (new $O())->create());
        $this->add('GET',  '/order/{id}',        fn ($p) => (new $O())->show((int)$p['id']));
        $this->add('POST', '/order/{id}/cancel', fn ($p) => (new $O())->cancel((int)$p['id']));

        $A = AdminPageController::class;
        $this->add('GET',  '/admin',                      fn () => (new $A())->dashboard());
        $this->add('GET',  '/admin/orders',               fn () => (new $A())->orders());
        $this->add('POST', '/admin/orders/{id}/status',   fn ($p) => (new $A())->updateOrderStatus((int)$p['id']));
        $this->add('GET',  '/admin/users',                fn () => (new $A())->users());
        $this->add('POST', '/admin/users/{id}/role',      fn ($p) => (new $A())->updateUserRole((int)$p['id']));
        $this->add('GET',  '/admin/products/new',         fn () => (new $A())->showCreate());
        $this->add('POST', '/admin/products',             fn () => (new $A())->store());
        $this->add('GET',  '/admin/products/{id}/edit',   fn ($p) => (new $A())->showEdit((int)$p['id']));
        $this->add('POST', '/admin/products/{id}',        fn ($p) => (new $A())->update((int)$p['id']));
        $this->add('POST', '/admin/products/{id}/delete', fn ($p) => (new $A())->destroy((int)$p['id']));

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
