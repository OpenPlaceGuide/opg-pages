<?php

namespace App\Services;

use App\Models\Area;
use App\Models\OsmId;
use App\Models\OsmInfo;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use App\Services\Cache;
use Illuminate\Support\Facades\Log;

class Overpass
{

    /**
     * @param array<OsmId> $places
     * @return array<OsmInfo>
     * @throws GuzzleException
     */
    public function fetchOsmInfo(array $places, array $areas = null): array
    {
        $objectQuerys = '';
        foreach ($places as $place) {
            $objectQuerys .= sprintf('%s(id:%d);', $place->osmType, $place->osmId,);
        }

        $data = $this->cachedRunQuery($objectQuerys, $areas);

        $currentObject = null;
        foreach ($data->elements as $element) {
            if ($element->type === 'area') {
                if ($currentObject === null) {
                    throw new \Exception('Area found but no element');
                }
                $currentArea = Repository::getInstance()->resolveArea($element->id);
                $result[] = $this->createOsmInfoFromElement($currentObject, $currentArea);;
                $currentObject = null;
            } else {
                if ($currentObject !== null) {
                    $result[] = $this->createOsmInfoFromElement($currentObject);;
                }
                $currentObject = $element;
            }
        }

        if ($currentObject !== null) {
            $result[] = $this->createOsmInfoFromElement($currentObject);;
        }

        return $result;
    }


    protected function cachedRunQuery(string $objectQueries, array $areas = null)
    {
        $query = $this->buildQuery($objectQueries, $areas);
        $cacheKey = md5($query);

        return Cache::remember($cacheKey, function () use ($query) {
            return $this->runQuery($query);
        });
    }

    /**
     * @param string $objectQuerys
     * @return \Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     */
    protected function runQuery(string $query): mixed
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://overpass-api.de/api/',
            'headers' => ['user-agent' => $this->buildUserAgent()]
        ]);


        $requestStart = microtime(true);
        try {
            $response = $client->post('interpreter',
                [
                    'form_params' =>
                        [
                            'data' => $query
                        ]
                ]
            );
        } catch (ClientException $e) {
            Log::error(sprintf(sprintf('Overpass error, time %fs full query:', microtime(true) - $requestStart) . PHP_EOL . $query));
            throw $e;
        }
        $requestTime = microtime(true) - $requestStart;
        Log::notice(sprintf('Overpass request for %s took %fs', $query, $requestTime));
        $data = json_decode($response->getBody());

        if (isset($data->remark) && str_contains($data->remark, 'timed out')) {
            Log::error(sprintf(sprintf('Overpass error, time %fs full query:', microtime(true) - $requestStart) . PHP_EOL . $query));
            throw new \Exception($data->remark);
        }

        return $data;
    }

    private function buildUserAgent()
    {
        $contact = config('app.technical_contact');
        $version = 'dev'; // FIXME: detect proper version
        if (empty($contact)) {
            throw new \InvalidArgumentException('Please configure APP_TECHNICAL_CONTACT in your environment file. This will be used to identify external requests');
        }
        return sprintf('opg-pages/%s (%s, %s)', $version, url(''), $contact);
    }

    /**
     * @param string $objectQuerys
     * @param array<Area>|null $areas
     * @return string
     */
    protected function buildQuery(string $objectQuerys, array $areas = null): string
    {
        if ($areas !== null) {
            $areasQuery = '';
            foreach($areas as $area) {
                if ($area->idInfo === null) {
                    continue;
                }
                $areasQuery .= sprintf('area(%d).areas;', $area->idInfo->getAreaId());
            }
            $outputQuery = <<<OVERPASS
foreach->.d(
  .d out center;
  (.d;.d >;)->.d;
  .d is_in -> .areas;
  (
    $areasQuery
  );
  out ids;
);
OVERPASS;

        } else {
            $outputQuery = <<<OVERPASS
out center;
OVERPASS;
        }

        $query = <<<OVERPASS
[out:json][timeout:10];
(
$objectQuerys
);
$outputQuery
>;
OVERPASS;
        return $query;
    }

    private function createOsmInfoFromElement(mixed $element, Area $area = null)
    {
        $idInfo = new OsmId($element->type, $element->id);
        if ($element->type === 'node') {
            return new OsmInfo($idInfo, $element->lat, $element->lon, $element->tags, $area);
        }

        return new OsmInfo($idInfo, $element->center->lat, $element->center->lon, $element->tags, $area);
    }

    public function fetchOsmOverview(\App\Models\PoiType $type, Area $area)
    {
        $key = $type->tags[0]['key']; // FIXME: support multiple tags, currently taking the first one
        $value = $type->tags[0]['value'];

        $innerQuery = sprintf('area(%d);', $area->idInfo->getAreaId());
        $innerQuery .= sprintf('nwr["%s"="%s"][name](area);', $key, $value);

        $result = [];
        $data = $this->cachedRunQuery($innerQuery);

        foreach ($data->elements as $element) {
            if ($element->type === 'area') {
                continue;
            }
            $result[] = $this->createOsmInfoFromElement($element);;
        }

        return $result;

    }


    /**
     * @param array<Area> $area
     */
    public function addTagsForAreas(array $areas): void
    {
        $objectQuerys = '';
        foreach ($areas as $area) {
            if ($area->idInfo) {
                $objectQuerys .= sprintf('area(%d);', $area->idInfo->getAreaId());
            }
        }

        $data = $this->cachedRunQueryWithBoundingBox($objectQuerys);

        foreach ($data->elements as $element) {
            // Convert OSM object ID back to area ID
            $areaId = null;
            if ($element->type === 'relation') {
                $areaId = $element->id + 3600000000;
            } elseif ($element->type === 'way') {
                $areaId = $element->id + 2400000000;
            } elseif ($element->type === 'node') {
                $areaId = $element->id;
            }

            if ($areaId && isset($areas[$areaId])) {
                $areas[$areaId]->tags = $element->tags;

                // Store bounding box information if available
                if (isset($element->bounds)) {
                    $areas[$areaId]->boundingBox = [
                        'north' => $element->bounds->maxlat,
                        'south' => $element->bounds->minlat,
                        'east' => $element->bounds->maxlon,
                        'west' => $element->bounds->minlon
                    ];
                }
            }
        }
    }

    /**
     * Run query with bounding box information
     */
    protected function cachedRunQueryWithBoundingBox(string $objectQueries)
    {
        $query = $this->buildQueryWithBoundingBox($objectQueries);
        $cacheKey = md5($query);

        return Cache::remember($cacheKey, function () use ($query) {
            return $this->runQuery($query);
        });
    }

    /**
     * Build query that includes bounding box information
     * For areas, we need to query the original OSM objects (ways/relations) to get bounding boxes
     */
    protected function buildQueryWithBoundingBox(string $objectQuerys): string
    {
        // Convert area queries to their original OSM object queries to get bounding boxes
        $osmObjectQueries = '';
        $lines = explode(';', $objectQuerys);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Extract area ID from area(ID) format
            if (preg_match('/area\((\d+)\)/', $line, $matches)) {
                $areaId = (int)$matches[1];

                // Convert area ID back to original OSM object
                if ($areaId >= 3600000000) {
                    // Relation
                    $osmId = $areaId - 3600000000;
                    $osmObjectQueries .= "relation(id:$osmId);";
                } elseif ($areaId >= 2400000000) {
                    // Way
                    $osmId = $areaId - 2400000000;
                    $osmObjectQueries .= "way(id:$osmId);";
                } else {
                    // Node (though nodes don't usually have areas)
                    $osmObjectQueries .= "node(id:$areaId);";
                }
            }
        }

        $query = <<<OVERPASS
[out:json][timeout:10];
(
$osmObjectQueries
);
out bb;
OVERPASS;

        return $query;
    }


}
