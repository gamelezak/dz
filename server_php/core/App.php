<?php

class App
{
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function config(string $path = '')
    {
        if ($path === '') return self::$config;
        $value = self::$config;
        foreach (explode('.', $path) as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) return null;
            $value = $value[$key];
        }
        return $value;
    }
}
