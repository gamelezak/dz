<?php

class ImageUploader
{

    public static function save(array $file): string
    {
        $dir      = App::config('uploads.dir');
        $maxSize  = (int)App::config('uploads.max_size');
        $allowed  = array_map('strtolower', (array)App::config('uploads.allowed'));

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Не удалось загрузить файл «' . ($file['name'] ?? '?') . '»');
        }
        if ((int)$file['size'] > $maxSize) {
            throw new RuntimeException('Файл «' . $file['name'] . '» больше 5 МБ');
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if ($ext === '') {

            $mime = (new finfo(FILEINFO_MIME_EXTENSION))->file($file['tmp_name']) ?: '';
            $ext  = strtolower($mime);
        }
        if (!in_array($ext, $allowed, true)) {
            throw new RuntimeException(
                'Недопустимый формат «' . ($file['name'] ?? '?') . '». Разрешены: ' . implode(', ', $allowed)
            );
        }

        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            throw new RuntimeException('Не удалось создать папку для картинок');
        }

        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {

            if (!rename($file['tmp_name'], $dir . '/' . $filename)) {
                throw new RuntimeException('Не удалось сохранить файл на сервере');
            }
        }
        @chmod($dir . '/' . $filename, 0644);
        return $filename;
    }

    public static function remove(?string $filename): void
    {
        if (!$filename || !preg_match('/^[\w.\-]+$/', $filename)) return;
        $path = App::config('uploads.dir') . '/' . $filename;
        if (is_file($path)) @unlink($path);
    }

    public static function removeAll(array $filenames): void
    {
        foreach ($filenames as $f) self::remove($f);
    }

}
