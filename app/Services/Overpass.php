<?php

namespace App\Services;

class Overpass {

    public function fetchFromXapi() {
        $client = new \GuzzleHttp\Client(['base_uri' => 'https://overpass.kumi.systems/api/']);
        $type = 'way';
        $id = '162817836';
        $query = <<<XAPI
[out:json][timeout:25];
(
  $type(id:$id);
);
out body;
>;
XAPI;

        $response = $client->get('interpreter?data=' . urlencode($query));
        $data = json_decode($response->getBody());
        return $data->elements[0]->tags;
    }

}
