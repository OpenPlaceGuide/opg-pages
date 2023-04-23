<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Branch;
use App\Models\OsmInfo;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Overpass
{

    /**
     * @param array<Branch> $places
     * @return array<OsmInfo>
     * @throws GuzzleException
     */
    public function fetchOsmInfo(array $places): array
    {
        $objectQuerys = '';
        foreach ($places as $place) {
            $objectQuerys .= sprintf('%s(id:%d);', $place->osmType, $place->osmId,);
            $result[$place->getKey()] = null;
        }

        $data = $this->cachedRunQuery($objectQuerys);
        foreach ($data->elements as $element) {
            if ($element->type === 'area') {
                continue;
            }
            $result[$element->type . $element->id] = $this->createOsmInfoFromElement($element);;
        }

        return array_values($result);
    }


    protected function cachedRunQuery($objectQuerys)
    {
        $query = $this->buildQuery($objectQuerys);
        $cacheKey = md5($query);

        return Cache::remember($cacheKey, 300, function () use ($query) {
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

    protected function buildQuery(string $objectQuerys): string
    {
        $query = <<<OVERPASS
[out:json][timeout:10];
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

    public function fetchOsmOverview(\App\Models\PoiType $type, Area $area)
    {
        $key = $type->tags[0]['key']; // FIXME: support multiple tags, currently taking the first one
        $value = $type->tags[0]['value'];

        $innerQuery = sprintf('area["%s"="%s"];', $area->tags[0]['key'], $area->tags[0]['value']); // FIXME: only first key/value supported
        $innerQuery .= sprintf('nwr["%s"="%s"](area);', $key, $value);

        $result = [];
        $data = $this->cachedRunQuery($innerQuery);

        foreach ($data->elements as $element) {
            if ($element->type === 'area') {
                continue;
            }
            $result[$element->type . $element->id] = $this->createOsmInfoFromElement($element);;
        }

        return array_values($result);

    }
}
