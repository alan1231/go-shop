<?php
// 商品商業邏輯：新增/編輯驗證、圖片上傳處理
class ProductService {
    private ProductRepository $repo;

    public function __construct() {
        $this->repo = new ProductRepository();
    }

    public function getAll(): array {
        return $this->repo->getAll();
    }

    public function getById(int $id): ?array {
        return $this->repo->getById($id);
    }

    // 新增商品，含圖片上傳；回傳結果陣列
    public function create(array $data, ?array $file): array {
        $name        = $data['name'] ?? '';
        $description = $data['description'] ?? '';
        $price       = (float)($data['price'] ?? 0);
        $listPrice   = $data['list_price'] !== '' ? (float)$data['list_price'] : null;
        $stock       = (int)($data['stock'] ?? 0);
        $listedStock = (int)($data['listed_stock'] ?? 0);
        $status      = $data['status'] ?? 'active';

        if (empty($name) || $price <= 0) {
            return ['success' => false, 'message' => '請填寫商品名稱且售價需大於 0'];
        }

        $imageResult = $this->handleImageUpload($file);
        if ($imageResult['error']) {
            return $imageResult;
        }

        $this->repo->create($name, $imageResult['name'], $description, $price, $listPrice, $stock, $listedStock, $status);
        return ['success' => true, 'message' => "商品「{$name}」新增成功！"];
    }

    // 編輯商品，可換圖；回傳結果陣列
    public function update(int $id, array $data, ?array $file): array {
        $p = $this->repo->getById($id);
        if (!$p) {
            return ['success' => false, 'message' => '商品不存在'];
        }

        $name        = $data['name'] ?? '';
        $description = $data['description'] ?? '';
        $price       = (float)($data['price'] ?? 0);
        $listPrice   = $data['list_price'] !== '' ? (float)$data['list_price'] : null;
        $stock       = (int)($data['stock'] ?? 0);
        $listedStock = (int)($data['listed_stock'] ?? 0);
        $status      = $data['status'] ?? 'active';
        $imageName   = $p['image'];

        if (empty($name) || $price <= 0) {
            return ['success' => false, 'message' => '請填寫商品名稱且售價需大於 0'];
        }

        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $result = $this->handleImageUpload($file, true);
            if ($result['error']) {
                return $result;
            }
            if ($p['image']) {
                $this->deleteImageFile($p['image']);
            }
            $imageName = $result['name'];
        }

        $this->repo->update($id, $name, $imageName, $description, $price, $listPrice, $stock, $listedStock, $status);
        return ['success' => true, 'message' => "商品「{$name}」修改成功！"];
    }

    // 處理圖片上傳，回傳 ['error' => bool, 'name' => ?string]
    private function handleImageUpload(?array $file, bool $required = false): array {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => false, 'name' => null];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return ['error' => true, 'success' => false, 'message' => '只允許上傳 JPG, PNG, GIF, WEBP 格式的圖片！'];
        }

        $uploadDir = __DIR__ . '/../../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $newName  = uniqid('img_', true) . '.' . $ext;
        $destPath = $uploadDir . $newName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['error' => true, 'success' => false, 'message' => '圖片上傳失敗，請檢查目錄權限'];
        }

        return ['error' => false, 'name' => $newName];
    }

    // 刪除舊圖檔
    private function deleteImageFile(string $filename): void {
        $path = __DIR__ . '/../../../uploads/' . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}