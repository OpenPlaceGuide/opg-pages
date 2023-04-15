<?php

namespace App\Services;

use App\Models\OsmInfo;
use App\Models\Branch;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class Overpass {

    /**
     * @param array<Branch> $places
     * @return array<OsmInfo>
     * @throws GuzzleException
     */
    public function fetchOsmInfo(array $places) {
        $objectQuerys = '';
        foreach($places as $place) {
            $objectQuerys .= sprintf('%s(id:%d);', $place->osmType, $place->osmId,);
            $result[$place->getKey()] = null;
        }

        $client = new \GuzzleHttp\Client(['base_uri' => 'https://overpass.kumi.systems/api/']);
        $query = $this->buildQuery($objectQuerys);

        $requestStart = microtime(true);
        $response = $client->get('interpreter?data=' . urlencode($query));
        $requestTime = microtime(true) - $requestStart;
        Log::notice(sprintf('Overpass request for %s took %fs', $objectQuerys, $requestTime));

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
$objectQuerys
);
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
