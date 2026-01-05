<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * Helper to generate deterministic idempotency keys for requests.
 */

namespace MercadoPago\AdbPayment\Helper;

class IdempotencyKeyGenerator
{
    /** 
     * External reference identifier of the order - Block name.
    */
	public const EXTERNAL_REFERENCE = 'external_reference';

	/**
     * Notification callback URL - Block name.
    */
	public const NOTIFICATION_URL = 'notification_url';

	/** 
     * Transaction amount field - Block name.
    */
	public const TRANSACTION_AMOUNT = 'transaction_amount';

	/**
	 * Generate a deterministic idempotency key based on request business fields.
	 * Fields required: external_reference, notification_url, transaction_amount, payments[].payment_method.id
	 * 
	 * @param array $request
	 * @return string
	 */
	public static function generate(array $request): ?string
	{
		$required = [
			self::EXTERNAL_REFERENCE,
			self::NOTIFICATION_URL,
			self::TRANSACTION_AMOUNT,
		];
		$missing = array_diff($required, array_keys($request));
		if (!empty($missing)) {
			return null;
		}

		$paymentMethodId = $request['payments'][0]['payment_method']['id'] ?? null;
		if (empty($paymentMethodId)) {
			return null;
		}

		return hash('sha256', implode([
			$request[self::EXTERNAL_REFERENCE],
			$request[self::NOTIFICATION_URL],
			$request[self::TRANSACTION_AMOUNT],
			$paymentMethodId,
		]));
	}

	/**
	 * Generate idempotency key for refund.
	 *
	 * @param string|null $mpOrderId MP Order ID
	 * @param string|null $amount Refund amount
	 * @param string|null $refundKey Unique key (order_increment_id + amount_already_refunded)
	 * @return string|null Returns null if any parameter is empty
	 */
	public static function generateForRefund(
		?string $mpOrderId,
		?string $amount,
		?string $refundKey
	): ?string {
		$values = [$mpOrderId, $amount, $refundKey];

		foreach ($values as $value) {
			if ($value === null || $value === '') {
				return null;
			}
		}

		return hash('sha256', implode('|', $values));
	}

	/**
	 * Generate idempotency key for cancel.
	 *
	 * @param string|null $mpOrderId MP Order ID
	 * @return string|null Returns null if mpOrderId is empty
	 */
	public static function generateForCancel(?string $mpOrderId): ?string
	{
		if (empty($mpOrderId)) {
			return null;
		}

		return hash('sha256', 'cancel|' . $mpOrderId);
	}
}


