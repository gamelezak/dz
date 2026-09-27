<?php

class View
{

    public static function render(string $template, array $data = []): string
    {
        $file = APP_ROOT . '/views/' . $template . '.php';
        if (!is_file($file)) {
            return '<h1>Шаблон не найден: ' . htmlspecialchars($template) . '</h1>';
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        $content = (string)ob_get_clean();

        if ($template !== 'layout' && !empty($data['useLayout'] ?? true) && $template !== '404embed') {
            $content = self::renderRaw('layout', ['pageTitle' => $data['pageTitle'] ?? 'SportShop',
                                                  'content'   => $content] + $data);
        }
        return $content;
    }

    private static function renderRaw(string $template, array $data = []): string
    {
        $file = APP_ROOT . '/views/' . $template . '.php';
        if (!is_file($file)) return '';
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string)ob_get_clean();
    }

    public static function renderPage(string $template, array $data = []): void
    {
        Response::html(self::render($template, $data));
    }
}
