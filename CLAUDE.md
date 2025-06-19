# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

OpenPlaceGuide Pages is a Laravel-based web application that provides place and business pages based on OpenStreetMap data. It integrates with external APIs like Mapillary for street view images and uses a git-based content management system.

## Commands

### Development
```bash
# Frontend development
npm run dev          # Start Vite dev server
npm run build        # Build for production

# Backend development  
composer install     # Install PHP dependencies
php artisan serve    # Local development server

# Testing
vendor/bin/phpunit   # Run PHP unit tests
npx cypress open     # E2E testing with Cypress
```

### Docker
```bash
docker build . -t opg-pages
docker compose up -d
```

## Architecture

### Technology Stack
- **Backend**: Laravel 10, PHP 8.1+
- **Frontend**: TailwindCSS 3.3, Alpine.js 3.12, Vite 4.0
- **Data**: Git-based repository with YAML configuration files
- **APIs**: Overpass (OpenStreetMap), Mapillary (street view images)

### Key Components

**Services** (`/app/Services/`):
- `Repository.php` - Git-based data repository management
- `Overpass.php` - OpenStreetMap API integration  
- `Mapillary.php` - Street view image integration
- `TagRenderer.php` - OSM tag processing and rendering
- `Cache.php` - Caching middleware

**Controllers** (`/app/Http/Controllers/`):
- `PageController.php` - Main routing logic for places, areas, and types
- `DetailRedirectController.php` - OSM ID-based redirects
- `SitemapController.php` - XML sitemap generation

**Models** (`/app/Models/`):
Domain models (not Eloquent): `Place.php`, `Area.php`, `OsmId.php`, `OsmInfo.php`, `PoiType.php`

### Route Structure
- `/{slug}` - Place or area pages
- `/{areaSlug}/{typeSlug}` - Type pages within areas  
- `/{osmTypeLetter}{osmId}/{slug?}` - OSM object pages (n/w/r + ID)
- `/assets/static-map/{lat}/{lon}/{slug}.png` - Dynamic map generation

### Data Architecture
- **Content Repository**: Git repository stored in `/storage/app/repositories/opg-data-ethiopia/`
- **YAML Configuration**: Places, areas, and POI types defined in YAML files
- **Asset Serving**: Repository symlinked to `/public/assets/ethiopia`
- **Caching**: Laravel cache with 5-minute expiration for API calls

### Multi-language Support
- Locale-based routing with language prefixes
- Fallback language handling in services
- Ethiopian font support (Noto Sans with Ethiopic)

## Testing

- **Feature Tests**: Route and integration testing in `/tests/Feature/`
- **Unit Tests**: Service-level testing in `/tests/Unit/`
- **E2E Tests**: Cypress tests in `/cypress/`
- **CI/CD**: GitHub Actions for automated testing

## Key Integrations

### Mapillary Integration
Street view images fetched using bounding box queries. See `/docs/MAPILLARY_INTEGRATION.md` for detailed documentation.

### OpenStreetMap Integration
Uses Overpass API for real-time OSM data fetching. Supports nodes, ways, and relations with comprehensive caching.

## Development Notes

- Database-free architecture using file-based data storage
- Multi-stage Docker build with Wolfi Linux base
- AGPL-licensed with attribution requirements
- Designed to integrate with OsmApp frontend via reverse proxy