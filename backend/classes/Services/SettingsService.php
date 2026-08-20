<?php

namespace App\Services;

use App\Config;
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
            'linepay' => $this->getLinePay(),
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

    public function getLinePay(): array {
        $defaultSandbox = Config::get('LINE_PAY_SANDBOX', 'true') !== 'false' ? '1' : '0';
        return [
            'channel_id' => $this->repo->get('linepay_channel_id', Config::get('LINE_PAY_CHANNEL_ID')),
            'channel_secret' => $this->repo->get('linepay_channel_secret', Config::get('LINE_PAY_CHANNEL_SECRET')),
            'sandbox' => $this->repo->get('linepay_sandbox', $defaultSandbox),
        ];
    }

    public function setLinePay(string $channelId, string $channelSecret, string $sandbox): void {
        $this->repo->set('linepay_channel_id', trim($channelId));
        $this->repo->set('linepay_channel_secret', trim($channelSecret));
        $this->repo->set('linepay_sandbox', $sandbox === '1' ? '1' : '0');
    }
}