<?php

namespace App\Services;

use App\Models\Area;
use App\Models\OsmId;
use App\Models\OsmInfo;
use App\Models\OsmMeta;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use App\Services\Cache;
use Illuminate\Support\Facades\Log;

class Overpass
{
    public const RECENT_CHANGES_DAYS = 30;
    public const RECENT_CHANGES_LIMIT = 100;

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

        $result = [];
        $osmObjects = [];
        $areaElements = [];

        // First pass: separate OSM objects from area elements
        foreach ($data->elements as $element) {
            if ($element->type === 'area') {
                $areaElements[] = $element;
            } else {
                $osmObjects[] = $element;
            }
        }

        // Second pass: process OSM objects and find their areas
        foreach ($osmObjects as $osmObject) {
            $matchingArea = null;

            // Find the first matching area for this OSM object
            // In most cases, we only care about the first/primary area
            if (!empty($areaElements)) {
                $matchingArea = Repository::getInstance()->resolveArea($areaElements[0]->id);
            }

            $result[] = $this->createOsmInfoFromElement($osmObject, $matchingArea);
        }

        return $result;
    }


    protected function cachedRunQuery(string $objectQueries, array $areas = null)
    {
        return $this->cachedRunRawQuery($this->buildQuery($objectQueries, $areas));
    }

    protected function cachedRunRawQuery(string $query, int $lifetime = Cache::LIFETIME)
    {
        $cacheKey = md5($query);

        return Cache::remember($cacheKey, function () use ($query) {
            return $this->runQuery($query);
        }, $lifetime);
    }

    /**
     * @param string $objectQuerys
     * @return \Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     */
    protected function runQuery(string $query): mixed
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => config('app.overpass_api_url'),
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

        $meta = null;
        if (isset($element->version, $element->timestamp, $element->changeset)) {
            $meta = new OsmMeta($element->version, $element->timestamp, $element->changeset, $element->user ?? null);
        }

        if ($element->type === 'node') {
            return new OsmInfo($idInfo, $element->lat, $element->lon, $element->tags, $area, $meta);
        }

        return new OsmInfo($idInfo, $element->center->lat, $element->center->lon, $element->tags, $area, $meta);
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
     * Named objects in the area that were added or edited since $days ago.
     *
     * @return array<OsmInfo> newest first, at most $limit entries
     */
    public function fetchRecentChanges(Area $area, int $days = self::RECENT_CHANGES_DAYS, int $limit = self::RECENT_CHANGES_LIMIT): array
    {
        if ($area->idInfo === null) {
            throw new \InvalidArgumentException(sprintf('Area %s has no OSM id', $area->slug));
        }

        $query = $this->buildRecentChangesQuery($area->idInfo->getAreaId(), $this->recentChangesSince($days));

        // Cached only briefly so the newsfeed stays fresh without hitting
        // Overpass on every page view.
        $data = $this->cachedRunRawQuery($query, Cache::SHORT_LIFETIME);

        $result = [];
        foreach ($data->elements as $element) {
            if ($element->type === 'area') {
                continue;
            }
            $result[] = $this->createOsmInfoFromElement($element);
        }

        usort($result, fn(OsmInfo $a, OsmInfo $b) => strcmp($b->meta->timestamp, $a->meta->timestamp));

        return array_slice($result, 0, $limit);
    }

    /**
     * Rounded down to midnight UTC for a stable, day-granular window.
     */
    protected function recentChangesSince(int $days): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify(sprintf('-%d days', $days))
            ->setTime(0, 0)
            ->format('Y-m-d\TH:i:s\Z');
    }

    protected function buildRecentChangesQuery(int $areaId, string $since): string
    {
        // Higher timeout than the other queries: date filters are slower.
        return <<<OVERPASS
[out:json][timeout:25];
area($areaId)->.a;
nwr(area.a)[name](newer:"$since");
out meta center;
OVERPASS;
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
