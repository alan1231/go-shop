<?php

namespace LinePay;

final class LinePayGateway {
    public function __construct(
        private readonly LinePayClient $client,
    ) {
    }

    public function isConfigured(): bool {
        return $this->client->isConfigured();
    }

    public function isSandbox(): bool {
        return $this->client->isSandbox();
    }

    public function start(LinePayOrder $order, string $confirmUrl, string $cancelUrl): LinePayStartResult {
        if (!$this->client->isConfigured()) {
            throw new LinePayException('LINE Pay is not configured.');
        }
        $resp = $this->client->request($order->toRequestBody($confirmUrl, $cancelUrl));
        if (!$resp->isSuccess()) {
            throw new LinePayException($resp->returnMessage(), $resp->returnCode(), $resp->httpCode());
        }
        $info = $resp->info();
        return new LinePayStartResult(
            (string)($info['transactionId'] ?? ''),
            (string)($info['paymentAccessToken'] ?? ''),
            (string)($info['paymentUrl']['web'] ?? ''),
            (string)($info['paymentUrl']['app'] ?? ''),
            $this->client->isSandbox(),
        );
    }

    public function check(string $transactionId): LinePayRawResult {
        return $this->client->checkStatus($transactionId);
    }

    public function refund(string $transactionId, int $amount): LinePayRawResult {
        return $this->client->refund($transactionId, $amount);
    }

    public function capture(string $transactionId, int $amount): LinePayStatus {
        $resp = $this->client->checkStatus($transactionId);
        $code = $resp->returnCode();
        if ($code === '0123') {
            return LinePayStatus::paid();
        }
        if ($code === '0110') {
            $confirm = $this->client->confirm($transactionId, $amount);
            if ($confirm->returnCode() === '0000') {
                return LinePayStatus::paid();
            }
            $recheck = $this->client->checkStatus($transactionId);
            if ($recheck->returnCode() === '0123') {
                return LinePayStatus::paid();
            }
            return LinePayStatus::pending();
        }
        if ($code === '0121' || $code === '0122') {
            return LinePayStatus::cancelled();
        }
        return LinePayStatus::pending();
    }
}