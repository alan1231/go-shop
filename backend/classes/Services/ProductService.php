<?php

class ProductService {
    private ProductRepository $repo;
    private Images $images;

    public function __construct(ProductRepository $repo, Images $images) {
        $this->repo = $repo;
        $this->images = $images;
    }

    public function getFilteredPage(string $keyword, string $category, int $page, int $perPage): array {
        $total = $this->repo->countSearch($keyword, $category);
        $items = $this->repo->search($keyword, $category, $perPage, ($page - 1) * $perPage);
        return Support::page($items, $total, $page, $perPage);
    }

    public function getActivePage(string $keyword, string $category, int $page, int $perPage): array {
        $total = $this->repo->countActive($keyword, $category);
        $items = $this->repo->findActive($keyword, $category, $perPage, ($page - 1) * $perPage);
        return Support::page($items, $total, $page, $perPage);
    }

    public function getAllCategories(): array {
        return $this->repo->getAllCategories();
    }

    public function getCategories(): array {
        return $this->repo->getCategories();
    }

    public function getById(int $id): ?array {
        return $this->repo->getById($id);
    }

    public function create(array $in): void {
        if (trim($in['name']) === '' || $in['price'] <= 0) {
            throw new ServiceException('請填寫商品名稱且售價需大於 0');
        }
        $imageName = '';
        if ($in['image'] !== '') {
            $imageName = $this->images->save($in['image'], $in['image_name']);
        }
        $this->repo->create(
            trim($in['name']),
            $imageName,
            $in['description'],
            trim($in['category']),
            $in['price'],
            $in['list_price'],
            $in['stock'],
            $in['listed_stock'],
            $in['status']
        );
    }

    public function update(int $id, array $in): void {
        $p = $this->repo->getById($id);
        if ($p === null) {
            throw new ServiceException('商品不存在');
        }
        if (trim($in['name']) === '' || $in['price'] <= 0) {
            throw new ServiceException('請填寫商品名稱且售價需大於 0');
        }
        $imageName = $p['image'];
        if ($in['image'] !== '') {
            $imageName = $this->images->save($in['image'], $in['image_name']);
            if ($p['image'] !== '') {
                $this->images->delete($p['image']);
            }
        }
        $this->repo->update(
            $id,
            trim($in['name']),
            $imageName,
            $in['description'],
            trim($in['category']),
            $in['price'],
            $in['list_price'],
            $in['stock'],
            $in['listed_stock'],
            $in['status']
        );
    }
}
