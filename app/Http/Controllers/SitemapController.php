<?php

namespace App\Http\Controllers;

use App\Services\Repository;
use DOMDocument;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\App;
use SimpleXMLElement;

class SitemapController extends BaseController
{
    private Repository $repository;

    public function __construct()
    {
        $repositoryName = 'ethiopia';
        $this->repository = new Repository($repositoryName);
    }

    public function index()
    {
        $entries = array_map(fn (string $url) => ['loc' => $url], array_merge(
            $this->getAreaUrls(),
            $this->getPlaceUrls(),
            $this->getTypeUrls()
        ));

        // The newsfeeds are refreshed every few minutes; "always" is the
        // sitemap protocol's highest change frequency.
        foreach ($this->getNewsfeedUrls() as $url) {
            $entries[] = ['loc' => $url, 'changefreq' => 'always'];
        }

        return response($this->generateSitemap($entries), 200, ['Content-Type' => 'application/xml']);
    }

    private function generateSitemap($entries) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset></urlset>');
        $xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($entries as $entry) {
            $urlElement = $xml->addChild('url');
            $urlElement->addChild('loc', htmlspecialchars($entry['loc']));
            if (isset($entry['changefreq'])) {
                $urlElement->addChild('changefreq', $entry['changefreq']);
            }
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
    }


    private function getAreaUrls()
    {
        $areas = $this->repository->listAreas();
        return $this->mapUrls($areas);
    }

    private function mapUrls($items)
    {
        $urls = array_map(function ($item): string {
            return $item->getUrl();
        }, $items);
        sort($urls);
        $urls = array_unique($urls);
        return $urls;
    }

    private function getNewsfeedUrls()
    {
        $urls = [];
        foreach ($this->repository->listAreas() as $area) {
            if ($area->idInfo === null) {
                continue;
            }
            $urls[] = route('newsfeed.' . App::currentLocale(), ['areaSlug' => $area->slug]);
        }
        sort($urls);
        return array_unique($urls);
    }

    private function getPlaceUrls()
    {
        $places = $this->repository->listPlaceIndex();
        return $this->mapUrls($places);
    }

    private function getTypeUrls()
    {
        $types = $this->repository->listTypes();

        $urls = [];
        foreach ($types as $item) {
            foreach ($this->repository->listAreas() as $area) {
                $urls[] = route('typesInArea.' . App::currentLocale(), [
                    'typeSlug' => $item->slug,
                    'areaSlug' => $area->slug
                ]);
            }
        }

        sort($urls);
        $urls = array_unique($urls);
        return $urls;
    }
}
