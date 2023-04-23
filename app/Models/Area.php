<?php

namespace App\Models;

use App\Services\Repository;

class Area
{
    public function __construct(public readonly Repository $repository, readonly  public string $slug, readonly public array $names, readonly public array $descriptions, readonly public array $tags) {

    }

}
