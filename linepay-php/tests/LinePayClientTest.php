<?php

namespace LinePay\Tests;

use LinePay\LinePayClient;
use LinePay\LinePayConfig;
use PHPUnit\Framework\TestCase;

final class LinePayClientTest extends TestCase {
    public function testSignatureMatchesHmacFormula(): void {
        $secret = 'test-secret';
        $path = '/v3/payments/request';
        $body = '{"amount":100}';
        $nonce = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

        $expected = base64_encode(hash_hmac('sha256', $secret . $path . $body . $nonce, $secret, true));
        $actual = LinePayClient::signature($secret, $path, $body, $nonce);

        $this->assertSame($expected, $actual);
        $this->assertSame(44, strlen($actual));
    }

    public function testSignatureIsDeterministic(): void {
        $first = LinePayClient::signature('s', '/p', '{}', 'n');
        $second = LinePayClient::signature('s', '/p', '{}', 'n');
        $this->assertSame($first, $second);
    }

    public function testIsConfiguredOnlyWhenBothCredentialsSet(): void {
        $this->assertFalse((new LinePayClient(new LinePayConfig('', '', true)))->isConfigured());
        $this->assertFalse((new LinePayClient(new LinePayConfig('id', '', true)))->isConfigured());
        $this->assertTrue((new LinePayClient(new LinePayConfig('id', 'secret', true)))->isConfigured());
    }

    public function testRequestBuildsHeadersBodyAndUrl(): void {
        $transport = new RecordingTransport([
            'returnCode' => '0000',
            'returnMessage' => 'Success.',
            'info' => ['transactionId' => 'txn-1'],
            'http_code' => 200,
        ]);
        $client = new LinePayClient(new LinePayConfig('ch-id', 'ch-secret', true), $transport);

        $result = $client->request([
            'amount' => 100,
            'currency' => 'TWD',
            'orderId' => 'ORD-1',
            'packages' => [],
            'redirectUrls' => ['confirmUrl' => 'https://a.test', 'cancelUrl' => 'https://a.test'],
        ]);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('txn-1', $result->info()['transactionId']);
        $this->assertCount(1, $transport->calls);

        $call = $transport->calls[0];
        $this->assertSame('POST', $call['method']);
        $this->assertStringStartsWith('https://sandbox-api-pay.line.me/v3/payments/request', $call['url']);
        $this->assertContains('X-LINE-ChannelId: ch-id', $call['headers']);
        $this->assertStringStartsWith('X-LINE-Authorization-Nonce: ', $call['headers'][3]);
        $this->assertStringStartsWith('X-LINE-Authorization: ', $call['headers'][2]);

        $decoded = json_decode($call['body'], true);
        $this->assertSame(100, $decoded['amount']);
        $this->assertSame('TWD', $decoded['currency']);
        $this->assertSame('ORD-1', $decoded['orderId']);
    }

    public function testProductionEndpointUsedWhenSandboxOff(): void {
        $transport = new RecordingTransport(['http_code' => 200]);
        $client = new LinePayClient(new LinePayConfig('id', 'secret', false), $transport);

        $client->checkStatus('txn-1');

        $this->assertStringStartsWith('https://api-pay.line.me/v3/payments/requests/', $transport->calls[0]['url']);
    }

    public function testNonJsonResponseBecomesFailedResult(): void {
        $transport = new RecordingTransport(['http_code' => 503]);
        $client = new LinePayClient(new LinePayConfig('id', 'secret', true), $transport);

        $result = $client->checkStatus('txn-1');

        $this->assertSame(503, $result->httpCode());
        $this->assertFalse($result->isSuccess());
        $this->assertSame('HTTP 503', $result->returnMessage());
    }
}