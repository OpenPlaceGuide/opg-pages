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
            $result[$place->getKey()] = null;
        }


        $data = $this->cachedRunQuery($objectQuerys, $areas);
        foreach ($data->elements as $element) {
            if ($element->type === 'area') {
                continue;
            }
            $idInfo = new OsmId($element->type, $element->id);

            $result[$element->type . $element->id] = $this->createOsmInfoFromElement($idInfo, $element);;
        }

        return array_values($result);
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
            'base_uri' => 'https://overpass.kumi.systems/api/',
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
  out;
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

    private function createOsmInfoFromElement(OsmId $idInfo, mixed $element)
    {
        if ($element->type === 'node') {
            return new OsmInfo($idInfo, $element->lat, $element->lon, $element->tags);
        }

        return new OsmInfo($idInfo, $element->center->lat, $element->center->lon, $element->tags);
    }

    public function fetchOsmOverview(\App\Models\PoiType $type, Area $area)
    {
        $key = $type->tags[0]['key']; // FIXME: support multiple tags, currently taking the first one
        $value = $type->tags[0]['value'];

        $innerQuery = sprintf('area(%d);', $area->idInfo->getAreaId());
        $innerQuery .= sprintf('nwr["%s"="%s"](area);', $key, $value);

        $result = [];
        $data = $this->cachedRunQuery($innerQuery);

        foreach ($data->elements as $element) {
            if ($element->type === 'area') {
                continue;
            }
            $idInfo = new OsmId($element->type, $element->id);
            $result[$element->type . $element->id] = $this->createOsmInfoFromElement($idInfo, $element);;
        }

        return array_values($result);

    }
}
