<?php

namespace Tests\Unit\Support;

use App\Support\Host;
use Tests\TestCase;

class HostTest extends TestCase
{
    public function test_from_url_or_host_strips_scheme_and_path(): void
    {
        $this->assertSame('model3d.ir', Host::fromUrlOrHost('https://model3d.ir/cart'));
        $this->assertSame('model3d.ir', Host::fromUrlOrHost('https://model3d.ir'));
        $this->assertSame('localhost:3000', Host::fromUrlOrHost('http://localhost:3000'));
        $this->assertSame('localhost:3000', Host::fromUrlOrHost('localhost:3000'));
        $this->assertNull(Host::fromUrlOrHost(''));
        $this->assertNull(Host::fromUrlOrHost(null));
    }

    public function test_cookie_domain_rejects_urls_and_preserves_leading_dot(): void
    {
        $this->assertSame('model3d.ir', Host::cookieDomain('https://model3d.ir/cart'));
        $this->assertSame('.model3d.ir', Host::cookieDomain('.model3d.ir'));
        $this->assertSame('localhost', Host::cookieDomain('localhost:8080'));
        $this->assertNull(Host::cookieDomain(''));
        $this->assertNull(Host::cookieDomain(null));
    }

    public function test_list_normalizes_csv_and_merges_extra(): void
    {
        $this->assertSame(
            ['model3d.ir', 'localhost:3000'],
            Host::list('https://model3d.ir/cart', ['http://localhost:3000'])
        );
    }
}
