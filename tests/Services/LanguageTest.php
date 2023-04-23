<?php

namespace Tests\Services;

use App\Services\Language;
use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    /**
     * @dataProvider transliterateData
     */
    public function testTransliterate($expected, $actual)
    {
        self::assertEquals($expected, Language::transliterate($actual));
    }

    public static function transliterateData()
    {
        return [
            'German' => ['Maeuse', 'Mäuse'],
            'Amharic' => ['emarinya', 'አማርኛ'],
        ];
    }
}
