<?php

namespace Tests\Unit;

use App\Support\Scalar;
use PHPUnit\Framework\TestCase;

class ScalarTest extends TestCase
{
    public function test_string_dan_angka_dikembalikan_apa_adanya(): void
    {
        $this->assertSame('2026-11-23', Scalar::str('2026-11-23'));
        $this->assertSame('12', Scalar::str(12));
        $this->assertSame('1.5', Scalar::str(1.5));
    }

    public function test_array_null_dan_objek_jadi_default(): void
    {
        $this->assertSame('', Scalar::str(['x']));
        $this->assertSame('', Scalar::str(null));
        $this->assertSame('', Scalar::str(new \stdClass));
        $this->assertSame('fallback', Scalar::str(['x'], 'fallback'));
    }
}
