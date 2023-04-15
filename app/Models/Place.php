<?php

namespace App\Models;

use App\Services\Repository;

class Place
{
    public function __construct(public readonly Repository $repository, public readonly string $slug, public readonly string $logo, public readonly array $branches, public readonly array $gallery = [])
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

    public function getProcessedGallery($language): array
    {
        $pictures = [];
        foreach ($this->gallery as $item) {
            $pictures[$item[$language]] = $this->getMediaPath($item['file']);
        }
        return $pictures;
    }
}
