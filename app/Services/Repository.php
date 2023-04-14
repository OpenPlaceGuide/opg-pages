<?php

namespace App\Services;

use Symfony\Component\Yaml\Yaml;

class Repository
{
    public function __construct(string $name)
    {
        $this->repository = $name;
    }

    public function getPlaceInfo($slug)
    {
        $yamlSource = file_get_contents(storage_path(
            sprintf('app/repositories/opg-data-%s/places/%s/place.yaml', $this->repository, $slug)
        ));

        return Yaml::parse($yamlSource);
    }
}
