<?php

namespace LinePay\Tests;

use LinePay\LinePayClient;
use LinePay\LinePayConfig;
use LinePay\LinePayGateway;
use LinePay\LinePayOrder;
use LinePay\LinePayProduct;
use PHPUnit\Framework\TestCase;

final class FakeLinePayClient extends LinePayClient {
    public const NO_RESPONSE = '__NO_RESPONSE__';

    private array $checkResponses = [];
    private array $confirmResponses = [];
    public array $calls = [];

    public function __construct(private readonly LinePayConfig $config, array $startResponse = []) {
        parent::__construct($config);
        $this->startResponse = $startResponse;
    }

    private array $startResponse;

    public function request(array $body): \LinePay\LinePayRawResult {
        $this->calls[] = ['type' => 'request', 'body' => $body];
        return $this->toResult($this->startResponse);
    }

    public function checkStatus(string $transactionId): \LinePay\LinePayRawResult {
        $this->calls[] = ['type' => 'check', 'id' => $transactionId];
        $resp = array_shift($this->checkResponses);
        return $this->toResult($resp ?? ['returnCode' => '', 'http_code' => 200]);
    }

    public function confirm(string $transactionId, int $amount): \LinePay\LinePayRawResult {
        $this->calls[] = ['type' => 'confirm', 'id' => $transactionId, 'amount' => $amount];
        $resp = array_shift($this->confirmResponses);
        return $this->toResult($resp ?? ['returnCode' => '', 'http_code' => 200]);
    }

    /**
     * @param array<string, mixed> $resp
     */
    public function queueCheck(array $resp): void {
        $this->checkResponses[] = $resp;
    }

    /**
     * @param array<string, mixed> $resp
     */
    public function queueConfirm(array $resp): void {
        $this->confirmResponses[] = $resp;
    }

    public function isSandbox(): bool {
        return $this->config->sandbox();
    }

    /**
     * @param array<string, mixed> $resp
     */
    private function toResult(array $resp): \LinePay\LinePayRawResult {
        return new \LinePay\LinePayRawResult(
            (string)($resp['returnCode'] ?? ''),
            (string)($resp['returnMessage'] ?? ''),
            is_array($resp['info'] ?? null) ? $resp['info'] : [],
            (int)($resp['http_code'] ?? 200),
        );
    }
}

final class LinePayGatewayTest extends TestCase {
    private function gateway(array $startResponse = []): array {
        $config = new LinePayConfig('id', 'secret', true);
        $client = new FakeLinePayClient($config, $startResponse);
        return [$client, new LinePayGateway($client)];
    }

    /**
     * @dataProvider captureProvider
     */
    public function testCapture(array $checks, array $confirms, string $expected): void {
        [$client, $gateway] = $this->gateway();
        foreach ($checks as $c) {
            $client->queueCheck($c);
        }
        foreach ($confirms as $c) {
            $client->queueConfirm($c);
        }

        $status = $gateway->capture('txn-1', 500);

        $this->assertSame($expected, $status->value());
    }

    public static function captureProvider(): array {
        return [
            'already reserved' => [
                [['returnCode' => '0123']],
                [],
                'paid',
            ],
            'confirmed straight' => [
                [['returnCode' => '0110']],
                [['returnCode' => '0000']],
                'paid',
            ],
            'confirm raced then recheck paid' => [
                [['returnCode' => '0110'], ['returnCode' => '0123']],
                [['returnCode' => '1152']],
                'paid',
            ],
            'confirm raced then recheck pending' => [
                [['returnCode' => '0110'], ['returnCode' => '0110']],
                [['returnCode' => '1152']],
                'pending',
            ],
            'cancelled' => [
                [['returnCode' => '0121']],
                [],
                'cancelled',
            ],
            'voided' => [
                [['returnCode' => '0122']],
                [],
                'cancelled',
            ],
            'unpaid still waiting' => [
                [['returnCode' => '0000']],
                [],
                'pending',
            ],
            'unknown code' => [
                [['returnCode' => '9999']],
                [],
                'pending',
            ],
        ];
    }

    public function testCaptureConfirmCalledWithAmount(): void {
        [$client, $gateway] = $this->gateway();
        $client->queueCheck(['returnCode' => '0110']);
        $client->queueConfirm(['returnCode' => '0000']);

        $gateway->capture('txn-9', 1234);

        $confirmCall = $client->calls[1];
        $this->assertSame('confirm', $confirmCall['type']);
        $this->assertSame('txn-9', $confirmCall['id']);
        $this->assertSame(1234, $confirmCall['amount']);
    }

    public function testCaptureDoesNotConfirmWhenAlreadyPaid(): void {
        [$client, $gateway] = $this->gateway();
        $client->queueCheck(['returnCode' => '0123']);

        $gateway->capture('txn-1', 100);

        $types = array_column($client->calls, 'type');
        $this->assertSame(['check'], $types);
    }

    public function testStartBuildsOrderAndReturnsResult(): void {
        [$client, $gateway] = $this->gateway([
            'returnCode' => '0000',
            'returnMessage' => 'Success.',
            'info' => [
                'transactionId' => 'txn-77',
                'paymentAccessToken' => 'token-abc',
                'paymentUrl' => [
                    'web' => 'https://sandbox-web-pay.line.me/1/77',
                    'app' => 'line://pay/77',
                ],
            ],
            'http_code' => 200,
        ]);

        $order = new LinePayOrder(
            300,
            'SHOP-0000000042',
            '購物訂單 #42',
            [new LinePayProduct('9', '蛋', 2, 150)],
        );

        $result = $gateway->start($order, 'https://a.test/confirm', 'https://a.test/cancel');

        $this->assertSame('txn-77', $result->transactionId());
        $this->assertSame('token-abc', $result->paymentAccessToken());
        $this->assertSame('https://sandbox-web-pay.line.me/1/77', $result->paymentUrlWeb());
        $this->assertSame('line://pay/77', $result->paymentUrlApp());
        $this->assertTrue($result->isSandbox());
        $this->assertSame(['request'], array_column($client->calls, 'type'));

        $body = $client->calls[0]['body'];
        $this->assertSame(300, $body['amount']);
        $this->assertSame('SHOP-0000000042', $body['orderId']);
        $this->assertCount(1, $body['packages'][0]['products']);
        $this->assertSame('9', $body['packages'][0]['products'][0]['id']);
        $this->assertSame(150, $body['packages'][0]['products'][0]['price']);
        $this->assertSame('https://a.test/confirm', $body['redirectUrls']['confirmUrl']);
    }

    public function testStartThrowsWhenNotConfigured(): void {
        $client = new FakeLinePayClient(new LinePayConfig('', '', true));
        $gateway = new LinePayGateway($client);

        $this->expectException(\LinePay\LinePayException::class);
        $gateway->start(new LinePayOrder(100, 'X', 'pkg', []), 'c', 'c');
    }

    public function testStartThrowsWhenRequestFails(): void {
        $client = new FakeLinePayClient(new LinePayConfig('id', 'secret', true), [
            'returnCode' => '1102',
            'returnMessage' => 'Something went wrong.',
            'http_code' => 400,
        ]);
        $gateway = new LinePayGateway($client);

        try {
            $gateway->start(new LinePayOrder(100, 'X', 'pkg', []), 'c', 'c');
            $this->fail('Expected LinePayException');
        } catch (\LinePay\LinePayException $e) {
            $this->assertSame('Something went wrong.', $e->getMessage());
            $this->assertSame('1102', $e->returnCode());
            $this->assertSame(400, $e->httpCode());
        }
    }
}