<?php

namespace App\Services;

use App\Models\OsmInfo;
use App\Models\Place;
use GuzzleHttp\Exception\GuzzleException;

class Overpass {

    /**
     * @param array<Place> $places
     * @return array<OsmInfo>
     * @throws GuzzleException
     */
    public function fetchOsmInfo(array $places) {
        $objectQuerys = '';
        foreach($places as $place) {
            $objectQuerys .= sprintf('%s(id:%d);', $place->osmType, $place->osmId,) . PHP_EOL;
            $result[$place->getKey()] = null;
        }

        $client = new \GuzzleHttp\Client(['base_uri' => 'https://overpass.kumi.systems/api/']);
        $query = $this->buildQuery($objectQuerys);
        $response = $client->get('interpreter?data=' . urlencode($query));

        $data = json_decode($response->getBody());

        foreach($data->elements as $element) {
            $result[$element->type . $element->id] = $this->createOsmInfoFromElement($element);;
        }
        return array_values($result);
    }

    protected function buildQuery(string $objectQuerys): string
    {
        $query = <<<OVERPASS
[out:json][timeout:25];
(
$objectQuerys);
out center;
>;
OVERPASS;
        return $query;
    }

    private function createOsmInfoFromElement(mixed $element)
    {
        if ($element->type === 'node') {
            return new OsmInfo($element->lat, $element->lon, $element->tags);
        }

        return new OsmInfo($element->center->lat, $element->center->lon, $element->tags);
    }

}
