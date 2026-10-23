<?php

namespace Tests\Services;

use App\Services\Language;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    #[DataProvider('transliterateData')]
    public function testTransliterate($expected, $actual)
    {
        self::assertEquals($expected, Language::transliterate($actual));
    }

    public static function transliterateData()
    {
        return [
            'German' => ['Maeuse', 'Mäuse'],
            'Amharic' => ['amarna', 'አማርኛ'],
        ];
    }
}
