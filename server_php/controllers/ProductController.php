<?php

class ProductController extends Controller
{
    private ProductModel $products;

    public function __construct()
    {
        parent::__construct();
        $this->products = new ProductModel();
    }

    public function index(): void
    {
        $search = $this->request->query('search');
        Response::json($this->products->all($search));
    }

    public function show(int $id): void
    {
        $product = $this->products->find($id);
        if ($product === null) {
            Response::json(['detail' => 'Товар не найден'], 404);
        }
        Response::json($product);
    }

    public function store(): void
    {
        $this->requireAdmin();

        $name        = trim((string)$this->request->input('name', ''));
        $price       = $this->request->input('price');
        $description = trim((string)$this->request->input('description', ''));

        $this->validate($name, $price);

        try {
            $image = $this->request->hasFile('image')
                ? ImageUploader::save($this->request->files('image')[0])
                : null;

            $extra = [];
            foreach ($this->request->files('extra_images') as $f) {
                $extra[] = ImageUploader::save($f); 

            }

            $id      = $this->products->create($name, round((float)$price, 2), $description, $image, $extra);
            $product = $this->products->find($id);

            Response::json($product, 201);
        } catch (RuntimeException $e) {

            Response::json(['detail' => $e->getMessage()], 400);
        }
    }

    public function update(int $id): void
    {
        $this->requireAdmin();

        $old = $this->products->find($id);
        if ($old === null) {
            Response::json(['detail' => 'Товар не найден'], 404);
        }

        $fields = [];
        $name   = $this->request->input('name');
        $price  = $this->request->input('price');
        $desc   = $this->request->input('description');

        if ($name !== null) {
            $name = trim((string)$name);
            if ($name === '') Response::json(['detail' => 'Название не может быть пустым'], 422);
            $fields['name'] = $name;
        }
        if ($price !== null) {
            if (!is_numeric($price) || (float)$price < 0) {
                Response::json(['detail' => 'Цена должна быть неотрицательным числом'], 422);
            }
            $fields['price'] = round((float)$price, 2);
        }
        if ($desc !== null) {
            $fields['description'] = trim((string)$desc) ?: null;
        }

        try {
            if ($this->request->hasFile('image')) {
                $new = ImageUploader::save($this->request->files('image')[0]);
                ImageUploader::remove($old['image']);      

                $fields['image'] = $new;
            }

            $extraAppend = [];
            foreach ($this->request->files('extra_images') as $f) {
                $extraAppend[] = ImageUploader::save($f);
            }

            $this->products->update($id, $fields, $extraAppend);
            Response::json($this->products->find($id));
        } catch (RuntimeException $e) {
            Response::json(['detail' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id): void
    {
        $this->requireAdmin();

        $files = $this->products->delete($id);
        if (!$files && $this->products->find($id) === null) {

            Response::json(['detail' => 'Товар не найден'], 404);
        }
        foreach ($files as $f) {
            ImageUploader::remove($f);
        }
        Response::json(['ok' => true]);
    }

    private function validate(string $name, $price): void
    {
        if ($name === '') {
            Response::json(['detail' => 'Укажите название товара'], 422);
        }

        $len = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);
        if ($len > 255) {
            Response::json(['detail' => 'Название слишком длинное (до 255 символов)'], 422);
        }
        if ($price === null || $price === '' || !is_numeric($price)) {
            Response::json(['detail' => 'Укажите цену товара числом'], 422);
        }
        if ((float)$price < 0) {
            Response::json(['detail' => 'Цена не может быть отрицательной'], 422);
        }
    }
}
