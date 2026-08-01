<?php

namespace App\Parsian;

class Verification
{
    /**
     * @var string The merchant ID.
     */
    private $merchantId;

    /**
     * @var int The amount of the payment.
     */
    private $token;

    /**
     * Constructs a new payment verification object.
     *
     * @param string $merchantId The merchant ID.
     * @param int $amount The amount of the payment.
     */
    public function __construct(string $merchantId, int $token)
    {
        $this->merchantId = $merchantId;
        $this->token = $token;
    }

    /**
     * Verifies the payment with the payment gateway.
     *
     * @return VerificationResponse The response from the payment gateway.
     */
    public function send(?object $client = null)
    {
        $url = "https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx?WSDL";

        $client ??= new \SoapClient($url);

        $params = array(
            'LoginAccount' => $this->merchantId,
            'Token' => $this->token,
        );

        $result = $client->confirmPayment(array('requestData' => $params));

        return new VerificationResponse($result);
    }

    /**
     * Sets the merchant id.
     *
     * @param string $merchantId.
     * @return self
     */
    public function merchantId(string $merchantId): self
    {
        $this->merchantId = $merchantId ?? config('payment-gateway.merchant_id');

        return $this;
    }

    /**
     * Sets the token.
     *
     * @param int $token The token.
     * @return self
     */
    public function token(int $token): self
    {
        $this->token = $token;

        return $this;
    }
}
