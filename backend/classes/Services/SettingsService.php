<?php

namespace App\Services;

use App\Repositories\SettingsRepository;
use App\ServiceException;

class SettingsService {
    private SettingsRepository $repo;

    public function __construct(SettingsRepository $repo) {
        $this->repo = $repo;
    }

    public function all(): array {
        return [
            'table_count' => $this->getTableCount(),
        ];
    }

    public function getTableCount(): int {
        return (int)$this->repo->get('table_count', '0');
    }

    public function setTableCount(int $count): void {
        if ($count < 0 || $count > 200) {
            throw new ServiceException('桌數必須介於 0 到 200 之間');
        }
        $this->repo->set('table_count', (string)$count);
    }
}