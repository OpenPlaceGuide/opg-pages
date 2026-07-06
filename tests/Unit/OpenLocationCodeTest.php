<?php

namespace Tests\Unit;

use App\Services\OpenLocationCode;
use PHPUnit\Framework\TestCase;

class OpenLocationCodeTest extends TestCase
{
    /**
     * Reference values cross-checked against the plus codes Google Maps / the
     * Open Location Code spec produce for these well-known coordinates.
     */
    public function test_encodes_reference_coordinates(): void
    {
        $this->assertSame('8FVC9G8F+6X', OpenLocationCode::encode(47.365590, 8.524997));
        $this->assertSame('87G7MXQ4+M5', OpenLocationCode::encode(40.689247, -74.044502));
        $this->assertSame('849VCWC8+X8', OpenLocationCode::encode(37.4224764, -122.0842499));
    }

    public function test_encodes_addis_ababa_coordinates(): void
    {
        // A coordinate from the Ethiopian dataset: 8-digit prefix + 2-digit cell.
        $code = OpenLocationCode::encode(8.99186, 38.85872);

        $this->assertMatchesRegularExpression('/^[23456789CFGHJMPQRVWX]{8}\+[23456789CFGHJMPQRVWX]{2}$/', $code);
    }

    public function test_clips_and_normalizes_out_of_range_coordinates(): void
    {
        // Must not throw or produce an out-of-alphabet character at the poles /
        // across the antimeridian.
        $this->assertMatchesRegularExpression('/^[23456789CFGHJMPQRVWX]{8}\+[23456789CFGHJMPQRVWX]{2}$/', OpenLocationCode::encode(90, 180));
        $this->assertMatchesRegularExpression('/^[23456789CFGHJMPQRVWX]{8}\+[23456789CFGHJMPQRVWX]{2}$/', OpenLocationCode::encode(-90, -180));
    }
}
