<?php

namespace App\Models;

use App\Services\Repository;

class PoiType
{
    public function __construct(
        public readonly Repository $repository,
        public readonly string $slug,
        public readonly ?string $logo,
        public readonly ?string $color,
        public readonly array $tags,
        public readonly array $name = [],
        public readonly array $plural = []
    )
    {
    }

    public function getLogoUrl(): string
    {
        return $this->getMediaPath($this->logo);
    }

    private function getMediaPath($fileName): string
    {
        return sprintf('assets/%s/%s/media/%s', $this->repository->name, $this->slug, $fileName);
    }
}
