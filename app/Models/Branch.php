<?php

namespace App\Models;

class Branch
{
    public function __construct(readonly public string $osmType, readonly public int $osmId) {

    }

    public function getKey()
    {
        return $this->osmType . $this->osmId;
    }
}
