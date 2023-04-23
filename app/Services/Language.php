<?php

namespace App\Services;

use Illuminate\Support\Facades\App;

class Language
{
    public static function transliterate($text)
    {
        return transliterator_transliterate('Any-Latin; de-ASCII; [\u0080-\u7fff] remove', $text);
    }


}
