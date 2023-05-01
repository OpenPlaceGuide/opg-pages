<?php

namespace App\Models;

use App\Facades\Fallback;
use App\Services\Repository;
use Illuminate\Support\Facades\App;

class Place
{
    /**
     * @param array<OsmId> $branches
     */
    public function __construct(
        public readonly Repository $repository,
        public readonly string $slug,
        public readonly string $logo,
        public readonly string $color,
        public readonly array $branches,
        public readonly array $gallery = [])
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

    public function getProcessedGallery(): array
    {
        $pictures = [];
        foreach ($this->gallery as $item) {
            $pictures[Fallback::resolve($item)] = $this->getMediaPath($item['file']);
        }
        return $pictures;
    }

    public function getKeys()
    {
        $keys = [];
        foreach($this->branches as $branch) {
            $keys[] = $branch->getKey();
        }
        return $keys;
    }

    public function getUrl(?OsmId $branch)
    {
        $url = route('page.' . App::currentLocale(), ['slug' => $this->slug]);
        if ($branch !== null) {
            $url .= '#' . $branch->getKey();
        }

        return $url;
    }

}
