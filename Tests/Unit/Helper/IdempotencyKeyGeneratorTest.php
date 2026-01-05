<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Helper;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Helper\IdempotencyKeyGenerator;

class IdempotencyKeyGeneratorTest extends TestCase
{
	private const BASE_REQUEST = [
		'external_reference' => 'order-123',
		'notification_url' => 'https://example.com/notify',
		'transaction_amount' => '99.90',
		'payments' => [['payment_method' => ['id' => 'pix']]],
	];

	private const BASE_REFUND = [
		'mp_order_id' => 'PPORD123',
		'amount' => '100.00',
		'refund_key' => '100000001-0',
	];


	public function testGenerateIsDeterministicAndStable(): void
	{
		$key1 = IdempotencyKeyGenerator::generate(self::BASE_REQUEST);

		// Reorder keys
		$reordered = [
			'external_reference' => 'order-123',
			'transaction_amount' => '99.90',
			'notification_url' => 'https://example.com/notify',
			'payments' => [['payment_method' => ['id' => 'pix']]],
		];
		$key2 = IdempotencyKeyGenerator::generate($reordered);

		$expected = hash('sha256', implode(['order-123', 'https://example.com/notify', '99.90', 'pix']));

		$this->assertSame($expected, $key1);
		$this->assertSame($key1, $key2);
	}

	/**
	 * @dataProvider generateDifferentFieldsProvider
	 */
	public function testGenerateChangesWithDifferentField(string $field, $value): void
	{
		$baseKey = IdempotencyKeyGenerator::generate(self::BASE_REQUEST);

		$modified = self::BASE_REQUEST;
		if ($field === 'payment_method_id') {
			$modified['payments'][0]['payment_method']['id'] = $value;
		} else {
			$modified[$field] = $value;
		}

		$this->assertNotSame($baseKey, IdempotencyKeyGenerator::generate($modified));
	}

	public function generateDifferentFieldsProvider(): array
	{
		return [
			'different external_reference' => ['external_reference', 'order-456'],
			'different notification_url' => ['notification_url', 'https://example.com/other'],
			'different transaction_amount' => ['transaction_amount', '100.00'],
			'different payment_method_id' => ['payment_method_id', 'card'],
		];
	}

	/**
	 * @dataProvider generateInvalidRequestProvider
	 */
	public function testGenerateReturnsNullForInvalidRequest(array $request): void
	{
		$this->assertNull(IdempotencyKeyGenerator::generate($request));
	}

	public function generateInvalidRequestProvider(): array
	{
		return [
			'empty request' => [[]],
			'missing notification_url' => [[
				'external_reference' => 'abc',
				'transaction_amount' => '10.00',
			]],
			'empty payment_method_id' => [[
				'external_reference' => 'order-123',
				'notification_url' => 'https://example.com/notify',
				'transaction_amount' => '99.90',
				'payments' => [['payment_method' => ['id' => '']]],
			]],
		];
	}

	/**
	 * @dataProvider generateForRefundValidProvider
	 */
	public function testGenerateForRefundWithValidParams(
		string $mpOrderId,
		string $amount,
		string $refundKey
	): void {
		$result = IdempotencyKeyGenerator::generateForRefund($mpOrderId, $amount, $refundKey);

		$this->assertNotNull($result);
		$this->assertEquals(hash('sha256', implode('|', [$mpOrderId, $amount, $refundKey])), $result);
		$this->assertEquals(64, strlen($result));
	}

	public function generateForRefundValidProvider(): array
	{
		return [
			'basic refund' => ['PPORD123', '100.00', '100000001-0'],
			'partial refund' => ['PPORD456', '50.00', '100000002-25'],
			'second partial' => ['PPORD789', '25.00', '100000003-75'],
		];
	}

	/**
	 * @dataProvider generateForRefundInvalidProvider
	 */
	public function testGenerateForRefundReturnsNullForInvalidParams(
		?string $mpOrderId,
		?string $amount,
		?string $refundKey
	): void {
		$this->assertNull(IdempotencyKeyGenerator::generateForRefund($mpOrderId, $amount, $refundKey));
	}

	public function generateForRefundInvalidProvider(): array
	{
		return [
			'all null' => [null, null, null],
			'null mpOrderId' => [null, '100.00', '100000001-0'],
			'null amount' => ['PPORD123', null, '100000001-0'],
			'null refundKey' => ['PPORD123', '100.00', null],
			'empty mpOrderId' => ['', '100.00', '100000001-0'],
			'empty amount' => ['PPORD123', '', '100000001-0'],
			'empty refundKey' => ['PPORD123', '100.00', ''],
		];
	}

	public function testGenerateForRefundIsDeterministic(): void
	{
		$params = array_values(self::BASE_REFUND);

		$result1 = IdempotencyKeyGenerator::generateForRefund(...$params);
		$result2 = IdempotencyKeyGenerator::generateForRefund(...$params);

		$this->assertEquals($result1, $result2);
	}

	/**
	 * @dataProvider generateForRefundDifferentParamsProvider
	 */
	public function testGenerateForRefundChangesWithDifferentParams(
		string $mpOrderId,
		string $amount,
		string $refundKey
	): void {
		$base = IdempotencyKeyGenerator::generateForRefund(...array_values(self::BASE_REFUND));
		$different = IdempotencyKeyGenerator::generateForRefund($mpOrderId, $amount, $refundKey);

		$this->assertNotEquals($base, $different);
	}

	public function generateForRefundDifferentParamsProvider(): array
	{
		return [
			'different mpOrderId' => ['PPORD456', '100.00', '100000001-0'],
			'different amount' => ['PPORD123', '50.00', '100000001-0'],
			'different refundKey' => ['PPORD123', '100.00', '100000001-25'],
			'all different' => ['PPORD999', '75.00', '100000002-50'],
		];
	}

	/**
	 * @dataProvider multiplePartialRefundsProvider
	 */
	public function testGenerateForRefundUniqueForMultiplePartialRefunds(
		string $refundKey1,
		string $refundKey2
	): void {
		$result1 = IdempotencyKeyGenerator::generateForRefund('PPORD123', '25.00', $refundKey1);
		$result2 = IdempotencyKeyGenerator::generateForRefund('PPORD123', '25.00', $refundKey2);

		$this->assertNotEquals($result1, $result2);
	}

	public function multiplePartialRefundsProvider(): array
	{
		return [
			'first vs second' => ['100000001-0', '100000001-25'],
			'second vs third' => ['100000001-25', '100000001-50'],
			'first vs third' => ['100000001-0', '100000001-50'],
		];
	}

	/**
	 * @dataProvider generateForCancelValidProvider
	 */
	public function testGenerateForCancelWithValidParams(string $mpOrderId): void
	{
		$result = IdempotencyKeyGenerator::generateForCancel($mpOrderId);

		$this->assertNotNull($result);
		$this->assertEquals(hash('sha256', 'cancel|' . $mpOrderId), $result);
		$this->assertEquals(64, strlen($result));
	}

	public function generateForCancelValidProvider(): array
	{
		return [
			'standard order id' => ['PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW'],
			'short order id' => ['PPORD123'],
			'numeric order id' => ['12345678901234567890'],
		];
	}

	/**
	 * @dataProvider generateForCancelInvalidProvider
	 */
	public function testGenerateForCancelReturnsNullForInvalidParams(?string $mpOrderId): void
	{
		$this->assertNull(IdempotencyKeyGenerator::generateForCancel($mpOrderId));
	}

	public function generateForCancelInvalidProvider(): array
	{
		return [
			'null mpOrderId' => [null],
			'empty mpOrderId' => [''],
		];
	}

	public function testGenerateForCancelIsDeterministic(): void
	{
		$mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW';

		$result1 = IdempotencyKeyGenerator::generateForCancel($mpOrderId);
		$result2 = IdempotencyKeyGenerator::generateForCancel($mpOrderId);

		$this->assertEquals($result1, $result2);
	}

	public function testGenerateForCancelChangesWithDifferentOrderId(): void
	{
		$result1 = IdempotencyKeyGenerator::generateForCancel('PPORD123');
		$result2 = IdempotencyKeyGenerator::generateForCancel('PPORD456');

		$this->assertNotEquals($result1, $result2);
	}

	public function testGenerateForCancelDiffersFromGenerateForRefund(): void
	{
		$mpOrderId = 'PPORD123';
		
		$cancelKey = IdempotencyKeyGenerator::generateForCancel($mpOrderId);
		$refundKey = IdempotencyKeyGenerator::generateForRefund($mpOrderId, '100.00', '100000001-0');

		$this->assertNotEquals($cancelKey, $refundKey);
	}

	public function testGenerateForCancelHasCancelPrefix(): void
	{
		$mpOrderId = 'PPORD123';
		
		$expected = hash('sha256', 'cancel|' . $mpOrderId);
		$result = IdempotencyKeyGenerator::generateForCancel($mpOrderId);

		$this->assertEquals($expected, $result);
	}
}
