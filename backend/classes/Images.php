<?php

namespace App;

class Images {
    private string $dir;

    private const ALLOWED = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(string $dir) {
        $this->dir = $dir;
    }

    public function save(string $data, string $filename): string {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED, true)) {
            throw new ServiceException('只允許上傳 JPG, PNG, GIF, WEBP 格式的圖片！');
        }
        if (!is_dir($this->dir) && !mkdir($this->dir, 0775, true) && !is_dir($this->dir)) {
            throw new ServiceException('圖片上傳失敗，請檢查目錄權限');
        }
        $name = 'img_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (file_put_contents($this->dir . '/' . $name, $data) === false) {
            throw new ServiceException('圖片上傳失敗，請檢查目錄權限');
        }
        return $name;
    }

    public function delete(string $filename): void {
        if ($filename === '') {
            return;
        }
        $full = $this->dir . '/' . $filename;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
