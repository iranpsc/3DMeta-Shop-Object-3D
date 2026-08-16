<?php

namespace App\Parsian;

class Error
{
    /**
     * @var int The error code.
     */
    private $code;

    /**
     * Error constructor.
     *
     * @param  string  $code  The error code.
     */
    public function __construct(int $code)
    {
        $this->code = $code;
    }

    /**
     * Get the error code.
     *
     * @return int The error code.
     */
    public function code(): int
    {
        return $this->code;
    }

    /**
     * Get the error message.
     *
     * @return string The error message.
     */
    public function message(): string
    {
        return match ($this->code) {
            -138 => 'تراکنش ناموفق می باشد',
            -127 => 'آدرس IP معتبر نمی باشد',
            58 => 'انجام تراکنش مربوطه توسط پایانه ی انجام دهنده مجاز نمی باشد',
            -1531 => 'تایید تراکنش ناموفق امکان پذیر نمی باشد',
            -112 => 'شناسه سفارش تکراری است.',
            default => 'خطای ناشناخته',
        };
    }
}
