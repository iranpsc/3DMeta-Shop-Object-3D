<?php

namespace App\Parsian;

class Request
{
    /**
     * @var string The merchant ID.
     */
    private $merchantId;

    /**
     * @var int The amount of the payment.
     */
    private $amount;

    /**
     * @var string The order ID.
     */
    private $orderId;

    /**
     * @var string The callback URL for the payment gateway to notify.
     */
    private $callbackUrl;

    /**
     * @var string Additional data for the payment request.
     */
    private $additionalData;

    /**
     * @var string The originator of the payment request.
     */
    private $originator;

    /**
     * Constructs a new payment request object.
     *
     * @param  string  $merchantId  The merchant ID.
     * @param  int  $amount  The amount of the payment.
     */
    public function __construct(string $merchantId, string $orderId, int $amount)
    {
        $this->merchantId = $merchantId;
        $this->orderId = $orderId;
        $this->amount = $amount;
    }

    /**
     * Sends the payment request to the payment gateway.
     *
     * @return RequestResponse The response from the payment gateway.
     */
    public function send(?object $client = null)
    {
        $url = 'https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx?WSDL';

        $params = [
            'LoginAccount' => $this->merchantId,
            'Amount' => $this->amount,
            'OrderId' => $this->orderId,
            'CallBackUrl' => $this->callbackUrl,
            'AdditionalData' => $this->additionalData,
            'Originator' => $this->originator,
        ];

        $client ??= new \SoapClient($url);

        $result = $client->SalePaymentRequest(['requestData' => $params]);

        return new RequestResponse($result);
    }

    /**
     * Sets the callback URL for the payment gateway to notify.
     *
     * @param  string  $callbackUrl  The callback URL.
     */
    public function callbackurl(string $callbackUrl): self
    {
        $this->callbackUrl = $callbackUrl;

        return $this;
    }

    /**
     * Sets additional data for the payment request.
     *
     * @param  string  $additionalData  The additional data.
     */
    public function additionalData(string $additionalData): self
    {
        $this->additionalData = $additionalData;

        return $this;
    }

    /**
     * Sets the originator of the payment request.
     *
     * @param  string  $originator  The originator.
     */
    public function originator(string $originator): self
    {
        $this->originator = $originator;

        return $this;
    }
}
