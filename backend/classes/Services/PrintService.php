<?php

namespace App\Services;

use App\Config;

class PrintService {
    private string $host;
    private int $port;

    public function __construct(string $host = '127.0.0.1', int $port = 9100) {
        $this->host = Config::get('PRINTER_HOST', $host);
        $this->port = (int) Config::get('PRINTER_PORT', (string) $port);
    }

    public function printReceipt(array $order, array $items): bool {
        return $this->send($this->format($order, $items));
    }

    private function send(string $data): bool {
        $fp = @fsockopen($this->host, $this->port, $errno, $errstr, 1.5);
        if ($fp === false) {
            return false;
        }
        fwrite($fp, $data);
        fclose($fp);
        return true;
    }

    private function format(array $order, array $items): string {
        $esc = "\x1B\x40";
        $cut = "\x1D\x56\x00";
        $lines = [];
        $lines[] = "===== 出餐單 =====\n";
        $lines[] = '訂單編號: #' . ($order['id'] ?? '') . "\n";
        $lines[] = '時間: ' . ($order['created_at'] ?? '') . "\n";
        $otype = ($order['order_type'] ?? 'dine_in') === 'takeout' ? '外帶' : '內用';
        if (($order['order_type'] ?? '') === 'dine_in' && !empty($order['table_number'])) {
            $otype .= ' ' . $order['table_number'] . ' 號桌';
        }
        $lines[] = '方式: ' . $otype . "\n";
        if (!empty($order['remark'])) {
            $lines[] = '備註: ' . $order['remark'] . "\n";
        }
        $lines[] = "----------------------------\n";
        foreach ($items as $it) {
            $name = (string)($it['name'] ?? '');
            $qty = (int)($it['quantity'] ?? 0);
            $price = (float)($it['price'] ?? 0);
            $lines[] = sprintf("%s x%d %s\n", $name, $qty, number_format($price * $qty, 0));
        }
        $lines[] = "----------------------------\n";
        $lines[] = '總計: ' . number_format((float)($order['total_amount'] ?? 0), 0) . "\n";
        return $esc . implode('', $lines) . $cut;
    }
}
