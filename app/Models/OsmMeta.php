<?php

namespace App\Models;

/**
 * OSM edit metadata as returned by Overpass "out meta".
 */
class OsmMeta
{
    public function __construct(
        public readonly int $version,
        public readonly string $timestamp,
        public readonly int $changeset,
        public readonly ?string $user = null
    )
    {
    }

    public function isNew(): bool
    {
        return $this->version === 1;
    }
}
