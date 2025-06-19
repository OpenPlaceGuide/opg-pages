<?php

namespace App\Services;

use App\Models\Place;
use App\Models\Area;
use App\Models\OsmId;
use App\Facades\Fallback;
use Symfony\Component\Yaml\Yaml;

class SchemaOrg
{
    private array $mappings;
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->loadMappings();
    }

    private function loadMappings(): void
    {
        $mappingPath = $this->repository->getSchemaMappingsFileName();
        
        if (file_exists($mappingPath)) {
            $content = file_get_contents($mappingPath);
            $this->mappings = Yaml::parse($content);
        } else {
            $this->mappings = [];
        }
    }

    /**
     * Generate schema.org JSON-LD markup for a place
     */
    public function generatePlaceSchema(Place $place, $type, $main, array $branches): array
    {
        $schemaType = $this->getSchemaTypeForPlace($type->slug);
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $this->extractSchemaType($schemaType),
            'name' => $this->getMultilingualValue(Fallback::field($main->tags, 'name')),
            'description' => Fallback::resolve($type->descriptions) ?? 'A ' . Fallback::resolve($type->name),
        ];

        // Add logo if available
        if ($place->logo) {
            $schema['logo'] = url($place->getLogoUrl());
        }

        // Add images from gallery
        if (!empty($place->gallery)) {
            $images = [];
            foreach ($place->getProcessedGallery() as $description => $imagePath) {
                $images[] = url($imagePath);
            }
            $schema['image'] = count($images) === 1 ? $images[0] : $images;
        }

        // Add locations for branches
        if (!empty($branches)) {
            $locations = [];
            foreach ($branches as $branch) {
                $location = $this->generateLocationSchema($branch, $type);
                if ($location) {
                    $locations[] = $location;
                }
            }
            
            if (count($locations) === 1) {
                // Single location - merge into main schema
                $schema = array_merge($schema, $locations[0]);
            } else {
                // Multiple locations - use hasPart or location array
                $schema['location'] = $locations;
            }
        }

        // Add additional properties from OSM tags
        $this->addOsmProperties($schema, $main->tags);

        return $schema;
    }

    /**
     * Generate schema.org JSON-LD markup for an area
     */
    public function generateAreaSchema(Area $area): array
    {
        $schemaType = $this->getSchemaTypeForArea($area->slug);
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $this->extractSchemaType($schemaType),
            'name' => $area->getFullName(),
        ];

        // Add description if available
        $description = Fallback::resolve($area->descriptions);
        if ($description) {
            $schema['description'] = $description;
        }

        // Add geographic information
        if ($area->hasBoundingBox()) {
            $center = $area->getCenterCoordinates();
            if ($center) {
                $schema['geo'] = [
                    '@type' => 'GeoCoordinates',
                    'latitude' => $center['lat'],
                    'longitude' => $center['lon']
                ];
            }
        }

        // Add sub-areas if available
        if (!empty($area->subAreas)) {
            $schema['containsPlace'] = [];
            foreach ($area->subAreas as $subAreaSlug) {
                $schema['containsPlace'][] = [
                    '@type' => 'Place',
                    'name' => $subAreaSlug, // Could be enhanced with actual area data
                ];
            }
        }

        // Add OSM information
        if ($area->idInfo) {
            $schema['additionalProperty'] = [
                [
                    '@type' => 'PropertyValue',
                    'name' => 'osm_type',
                    'value' => $area->idInfo->osmType
                ],
                [
                    '@type' => 'PropertyValue', 
                    'name' => 'osm_id',
                    'value' => $area->idInfo->osmId
                ]
            ];
        }

        return $schema;
    }

    /**
     * Generate location schema for a branch
     */
    private function generateLocationSchema($branch, $type): ?array
    {
        if (!isset($branch->lat, $branch->lon)) {
            return null;
        }

        $schemaType = $this->getSchemaTypeForPlace($type->slug);

        $location = [
            '@type' => $this->extractSchemaType($schemaType),
            'name' => Fallback::field($branch->tags, 'name'),
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $branch->lat,
                'longitude' => $branch->lon
            ]
        ];

        // Add address information if available from tags
        $this->addAddressFromTags($location, $branch->tags);

        // Add contact information if available
        $this->addContactFromTags($location, $branch->tags);

        // Add area information
        if ($branch->area !== null) {
            $location['containedInPlace'] = [
                '@type' => 'AdministrativeArea',
                'name' => $branch->area->getFullName(),
                'url' => url($branch->area->getUrl())
            ];
        }

        return $location;
    }

    /**
     * Add address information from OSM tags
     */
    private function addAddressFromTags(array &$schema, \stdClass $tags): void
    {
        $addressFields = [
            'addr:street' => 'streetAddress',
            'addr:housenumber' => 'streetAddress',
            'addr:city' => 'addressLocality',
            'addr:state' => 'addressRegion',
            'addr:postcode' => 'postalCode',
            'addr:country' => 'addressCountry'
        ];

        $address = [];
        foreach ($addressFields as $osmKey => $schemaKey) {
            $value = Fallback::field($tags, $osmKey);
            if ($value) {
                $address[$schemaKey] = $value;
            }
        }

        if (!empty($address)) {
            $schema['address'] = array_merge(['@type' => 'PostalAddress'], $address);
        }
    }

    /**
     * Add contact information from OSM tags
     */
    private function addContactFromTags(array &$schema, \stdClass $tags): void
    {
        $contactMappings = [
            'phone' => 'telephone',
            'website' => 'url',
            'email' => 'email',
            'opening_hours' => 'openingHours'
        ];

        foreach ($contactMappings as $osmKey => $schemaKey) {
            $value = Fallback::field($tags, $osmKey);
            if ($value) {
                $schema[$schemaKey] = $value;
            }
        }
    }

    /**
     * Add additional properties from OSM tags
     */
    private function addOsmProperties(array &$schema, \stdClass $tags): void
    {
        // Add OSM-specific properties as additionalProperty
        $osmProperties = ['amenity', 'shop', 'tourism', 'leisure', 'cuisine'];
        $additionalProperties = [];

        foreach ($osmProperties as $key) {
            $value = Fallback::field($tags, $key);
            if ($value) {
                $additionalProperties[] = [
                    '@type' => 'PropertyValue',
                    'name' => $key,
                    'value' => $value
                ];
            }
        }

        if (!empty($additionalProperties)) {
            $schema['additionalProperty'] = $additionalProperties;
        }
    }

    /**
     * Get schema.org type for a place type
     */
    private function getSchemaTypeForPlace(string $typeSlug): string
    {
        return $this->mappings['place_types'][$typeSlug] 
            ?? $this->mappings['place_types']['~fallback_type'] 
            ?? 'https://schema.org/LocalBusiness';
    }

    /**
     * Get schema.org type for an area
     */
    private function getSchemaTypeForArea(string $areaSlug): string
    {
        return $this->mappings['area_types'][$areaSlug] 
            ?? $this->mappings['area_types']['areas'] 
            ?? 'https://schema.org/AdministrativeArea';
    }

    /**
     * Extract the schema type name from full URL
     */
    private function extractSchemaType(string $schemaUrl): string
    {
        return basename($schemaUrl);
    }

    /**
     * Handle multilingual values
     */
    private function getMultilingualValue($value): string
    {
        if (is_array($value)) {
            $defaultLang = $this->mappings['languages']['default'] ?? 'en';
            return $value[$defaultLang] ?? reset($value);
        }
        return $value;
    }

    /**
     * Generate JSON-LD script tag
     */
    public function renderJsonLd(array $schema): string
    {
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}