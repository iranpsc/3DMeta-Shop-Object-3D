<?php

namespace App\Parsian;

use Illuminate\Http\RedirectResponse;

class RequestResponse
{
    /**
     * @var int The status code of the response.
     */
    private $status;

    /**
     * @var string The message associated with the response.
     */
    private $message;

    /**
     * @var string The token associated with the response.
     */
    private $token;

    /**
     * RequestResponse constructor.
     *
     * @param  $result  The response data.
     */
    public function __construct($result)
    {
        $this->status = $result->SalePaymentRequestResult->Status;
        $this->message = $result->SalePaymentRequestResult->Message;
        $this->token = $result->SalePaymentRequestResult->Token;
    }

    /**
     * Check if the response indicates success.
     *
     * @return bool True if the response is successful, false otherwise.
     */
    public function success(): bool
    {
        return $this->status === 0 && $this->token > 0;
    }

    /**
     * Get the message associated with the response.
     *
     * @return string The response message.
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * Get the token associated with the response.
     *
     * @return string The response token.
     */
    public function token(): string
    {
        return $this->token;
    }

    /**
     * Get the URL for redirecting the user to complete the payment.
     *
     * @return string The redirect URL.
     */
    public function url(): string
    {
        if (! $this->success()) {
            return null;
        }

        $url = 'https://pec.shaparak.ir/NewIPG/?Token=';

        return $url.$this->token;
    }

    /**
     * Get the redirect response for completing the payment.
     *
     * @return RedirectResponse|null The redirect response, or null if the response is not successful.
     */
    public function redirect(): ?RedirectResponse
    {
        $url = $this->url();

        return $url ? redirect($url) : null;
    }

    /**
     * Get the error object associated with the response.
     *
     * @return Error The error object.
     */
    public function error(): Error
    {
        return new Error($this->status);
    }
}
