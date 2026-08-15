<?php

use App\Parsian\Parsian;

/**
 * Get the payment gateway instance.
 *
 * @return Parsian
 */
if (! function_exists('parsian')) {
    function parsian(): Parsian
    {
        return app(Parsian::class);
    }
}
