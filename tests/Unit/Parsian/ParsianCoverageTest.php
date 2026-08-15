<?php

namespace Tests\Unit\Parsian;

use App\Parsian\Error;
use App\Parsian\Parsian;
use App\Parsian\Request;
use App\Parsian\RequestResponse;
use App\Parsian\Verification;
use App\Parsian\VerificationResponse;
use Tests\TestCase;

class ParsianCoverageTest extends TestCase
{
    public function test_error_messages_cover_known_codes(): void
    {
        $this->assertSame('تراکنش ناموفق می باشد', (new Error(-138))->message());
        $this->assertSame('آدرس IP معتبر نمی باشد', (new Error(-127))->message());
        $this->assertSame(
            'انجام تراکنش مربوطه توسط پایانه ی انجام دهنده مجاز نمی باشد',
            (new Error(58))->message()
        );
        $this->assertSame('تایید تراکنش ناموفق امکان پذیر نمی باشد', (new Error(-1531))->message());
        $this->assertSame('شناسه سفارش تکراری است.', (new Error(-112))->message());
        $this->assertSame('خطای ناشناخته', (new Error(999))->message());
        $this->assertSame(-138, (new Error(-138))->code());
    }

    public function test_parsian_fluent_setters(): void
    {
        config(['payment-gateway.merchant_id' => 'merchant-1']);

        $parsian = new Parsian;
        $request = $parsian->merchantId('m-2')->amount(1000)->orderId('ord-1')->request();
        $this->assertInstanceOf(Request::class, $request);

        $verification = $parsian->token(123)->verification();
        $this->assertInstanceOf(Verification::class, $verification);
        $this->assertSame(123, $parsian->token);
    }

    public function test_request_response_success_and_url(): void
    {
        $result = (object) [
            'SalePaymentRequestResult' => (object) [
                'Status' => 0,
                'Message' => 'ok',
                'Token' => 12345,
            ],
        ];

        $response = new RequestResponse($result);
        $this->assertTrue($response->success());
        $this->assertSame('ok', $response->message());
        $this->assertEquals(12345, $response->token());
        $this->assertStringContainsString('Token=12345', $response->url());
        $this->assertNotNull($response->redirect());
    }

    public function test_request_response_failure(): void
    {
        $result = (object) [
            'SalePaymentRequestResult' => (object) [
                'Status' => -138,
                'Message' => 'fail',
                'Token' => 0,
            ],
        ];

        $response = new RequestResponse($result);
        $this->assertFalse($response->success());
        $this->assertSame(-138, $response->error()->code());
    }

    public function test_verification_response_success_and_failure(): void
    {
        $ok = new VerificationResponse((object) [
            'ConfirmPaymentResult' => (object) [
                'Status' => 0,
                'RRN' => '999',
            ],
        ]);
        $this->assertTrue($ok->success());
        $this->assertSame(0, $ok->status());
        $this->assertSame('999', $ok->referenceId());
        $this->assertNull($ok->cardHash());

        $fail = new VerificationResponse((object) [
            'ConfirmPaymentResult' => (object) [
                'Status' => -138,
                'RRN' => 0,
            ],
        ]);
        $this->assertFalse($fail->success());
        $this->assertSame(-138, $fail->error()->code());
    }

    public function test_request_builder_setters(): void
    {
        $request = new Request('merchant', 'order-1', 1000);
        $this->assertSame($request, $request->callbackurl('http://example.com/cb'));
        $this->assertSame($request, $request->additionalData('extra'));
        $this->assertSame($request, $request->originator('origin'));
    }

    public function test_request_and_verification_send_with_injected_client(): void
    {
        $soapResult = (object) [
            'SalePaymentRequestResult' => (object) [
                'Status' => 0,
                'Message' => 'ok',
                'Token' => 555,
            ],
        ];

        $client = new class($soapResult)
        {
            public function __construct(private object $result) {}

            public function SalePaymentRequest(array $args): object
            {
                return $this->result;
            }
        };

        $request = new Request('merchant', 'order-1', 1000);
        $request->callbackurl('http://example.com/cb');
        $response = $request->send($client);
        $this->assertTrue($response->success());

        $confirm = (object) [
            'ConfirmPaymentResult' => (object) [
                'Status' => 0,
                'RRN' => '777',
            ],
        ];

        $verifyClient = new class($confirm)
        {
            public function __construct(private object $result) {}

            public function confirmPayment(array $args): object
            {
                return $this->result;
            }
        };

        $verification = new Verification('merchant', 555);
        $verifyResponse = $verification->send($verifyClient);
        $this->assertTrue($verifyResponse->success());
    }
}
