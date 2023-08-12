<?php

namespace App\Facades;

use Illuminate\Support\Facades\App;

class FallbackImplementation
{
    public function resolve($objectOrArray, $language = null): string
    {
        if (!$language) {
            $language = App::currentLocale();
        }

        $array = (array) $objectOrArray;

        if (!empty($array[$language])) {
            return $array[$language];
        }

        if (!empty($array['default'])) {
            return $array['default'];
        }

        if (!empty($array[App::getFallbackLocale()])) {
            return $array[App::getFallbackLocale()];
        }

        return '';
    }

    public function field($objectOrArray, $field, $delimiter = ':', $language = null): string
    {
        $array = (array) $objectOrArray;

        $result['default'] = $array[$field] ?? null;

        $prefix = $field . $delimiter;

        foreach($array as $key=>$value)
        {
            if (str_starts_with($key, $prefix)) {
                $lang = substr($key, strlen($prefix));
                $result[$lang] = $value;
            }
        }

        return $this->resolve($result, $language);
    }
}
