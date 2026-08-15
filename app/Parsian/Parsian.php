<?php

namespace App\Parsian;

class Parsian
{
    /**
     * @var string The order ID for the payment.
     */
    private $orderId;

    /**
     * @var int The amount of the payment.
     */
    private $amount;

    /**
     * @var int The merchant Id.
     */
    private $merchantId;

    /**
     * @var string The token for the payment.
     */
    public $token;

    /**
     * Sets the order ID for the payment.
     *
     * @param  string  $orderId  The order ID.
     */
    public function orderId(string $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * Sets the amount of the payment.
     *
     * @param  int  $amount  The amount.
     */
    public function amount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Sets the merchant id.
     */
    public function merchantId(string $merchantId): self
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    /**
     * Sets the token.
     *
     * @param  int  $token  The token.
     */
    public function token(int $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Creates a new payment request.
     *
     * @return Request The payment request.
     */
    public function request(): Request
    {
        $merchantId = $this->merchantId ?? config('payment-gateway.merchant_id');

        return new Request($merchantId, $this->orderId, $this->amount);
    }

    /**
     * Performs verification for the payment.
     *
     * @return Verification The payment verification.
     */
    public function verification(): Verification
    {
        $merchantId = $this->merchantId ?? config('payment-gateway.merchant_id');

        return new Verification($merchantId, $this->token);
    }
}
