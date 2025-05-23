<?php

namespace App\Models;

use App\Services\Repository;

class PoiType
{
    public function __construct(
        public readonly Repository $repository,
        public readonly string $slug,
        public readonly ?string $logo,
        public readonly ?string $icon,
        public readonly ?string $color,
        public readonly array $tags,
        public readonly array $name = [],
        public readonly array $descriptions,
        public readonly array $plural = []
    )
    {
    }

    public function getLogoUrl(): ?string
    {
        if (empty($this->logo)) {
            return null;
        }
        return url($this->getMediaPath($this->logo));
    }

    private function getMediaPath($fileName): string
    {
        return sprintf('assets/%s/%s/media/%s', $this->repository->name, $this->slug, $fileName);
    }
}
