<?php

class PageController extends Controller
{
    private ProductModel $products;

    public function __construct()
    {
        parent::__construct();
        $this->products = new ProductModel();
    }

    public function catalog(): void
    {
        $search   = trim((string)$this->request->query('search', ''));
        $products = $this->products->all($search !== '' ? $search : null);
        View::renderPage('index', [
            'pageTitle' => 'SportShop — магазин спортивных товаров',
            'search'    => $search,
            'products'  => $products,
        ]);
    }

    public function product(int $id): void
    {
        $product = $this->products->find($id);
        if ($product === null) {
            View::renderPage('404', ['pageTitle' => 'SportShop — товар не найден']);
        }
        View::renderPage('product', [
            'pageTitle' => 'SportShop — ' . $product['name'],
            'p'         => $product,
        ]);
    }

    public function cart(): void
    {
        View::renderPage('cart', ['pageTitle' => 'SportShop — корзина']);
    }
}
