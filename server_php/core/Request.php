<?php

class Request
{
    private array $get;
    private array $post;
    private array $json = [];

    private array $multipartFiles = [];

    public function __construct()
    {
        $this->get  = $_GET ?? [];
        $this->post = $_POST ?? [];

        $method      = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if ($method === 'POST') {

            if (str_contains($contentType, 'application/json')) {
                $decoded = json_decode((string)file_get_contents('php://input'), true);
                if (is_array($decoded)) $this->json = $decoded;
            }
            return;
        }

        $raw = (string)file_get_contents('php://input');
        if ($raw === '') return;

        if (preg_match('#boundary="?([^";\s]+)"?#', $contentType, $bm)
            && str_contains($raw, '--' . $bm[1])) {
            $this->parseMultipart($raw, $bm[1]);
        } elseif (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $this->json = $decoded;
        } else {
            parse_str($raw, $parsed);
            if (is_array($parsed)) $this->post = $parsed + $this->post;
        }
    }

    private function parseMultipart(string $raw, string $boundary): void
    {
        foreach (explode('--' . $boundary, $raw) as $part) {
            $part = ltrim($part, "\r\n");
            if ($part === '' || rtrim($part, "-\r\n") === '') continue;
            $sep = strpos($part, "\r\n\r\n");
            if ($sep === false) continue;
            $headers = substr($part, 0, $sep);
            $body    = rtrim(substr($part, $sep + 4), "\r\n");
            if (!preg_match('/name="([^"]*)"/i', $headers, $nm)) continue;
            $name = $nm[1];

            if (preg_match('/filename="([^"]*)"/i', $headers, $fm) && $fm[1] !== '') {
                $tmp = tempnam(sys_get_temp_dir(), 'sp_up_');
                file_put_contents($tmp, $body);
                $this->multipartFiles[$name][] = [
                    'name'     => $fm[1],
                    'tmp_name' => $tmp,
                    'size'     => strlen($body),
                    'error'    => UPLOAD_ERR_OK,
                ];
            } else {
                $this->post[$name] = $body;
            }
        }
    }

    public function input(string $key, $default = null)
    {
        return $this->post[$key] ?? $this->json[$key] ?? $this->get[$key] ?? $default;
    }

    /** Все POST-параметры (в т.ч. разобранные из multipart/json тела). */
    public function all(): array
    {
        return $this->post + $this->json;
    }

    public function query(string $key, $default = null)
    {
        return $this->get[$key] ?? $default;
    }

    public function hasFile(string $key): bool
    {
        return (bool)$this->files($key);
    }

    public function files(string $key): array
    {
        if (isset($this->multipartFiles[$key])) {
            return $this->multipartFiles[$key];
        }
        if (!isset($_FILES[$key])) return [];
        $f = $_FILES[$key];
        if (!is_array($f['name'])) {
            return $f['error'] === UPLOAD_ERR_NO_FILE ? [] : [$f];
        }
        $out = [];
        foreach (array_keys($f['name']) as $i) {
            if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            $out[] = [
                'name'     => $f['name'][$i],
                'tmp_name' => $f['tmp_name'][$i],
                'size'     => $f['size'][$i],
                'error'    => $f['error'][$i],
            ];
        }
        return $out;
    }
}
