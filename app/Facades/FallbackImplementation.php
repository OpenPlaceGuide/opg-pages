<?php

namespace App\Facades;

use Illuminate\Support\Facades\App;

class FallbackImplementation
{
    public function resolve($objectOrArray): string
    {
        $lang = App::currentLocale();
        $array = (array) $objectOrArray;

        if (!empty($array[$lang])) {
            return $array[$lang];
        }

        if (!empty($array['default'])) {
            return $array['default'];
        }

        if (!empty($array[App::getFallbackLocale()])) {
            return $array[App::getFallbackLocale()];
        }

        return '(empty)';
    }

    public function field($objectOrArray, $field, $delimiter = ':'): string
    {
        $array = (array) $objectOrArray;

        $result['default'] = $array[$field] ?? null;

        $prefix = $field . $delimiter;

        foreach($array as $key=>$value)
        {
            if (str_starts_with($key, $prefix)) {
                $language = substr($key, strlen($prefix));
                $result[$language] = $value;
            }
        }

        return $this->resolve($result);
    }
}
