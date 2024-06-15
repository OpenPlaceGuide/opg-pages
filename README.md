# OpenPlaceGuide Pages (opg-pages)

*WIP*

License: Undecided, please contact us if you are interested to use any of this.

## Stack

* Laravel 10
* PHP 8.1

## Fonts for Static Map

see `resources/fonts/README.md`

## Configuration (Environment variables)

`APP_TECHNICAL_CONTACT`: Define an email address where you are reachable. This is used in the user agent for calling
external APIs and crucial to contact you in any case of malfunction. The email will not be published on the page.

## Functionality

This provides place and business pages based on OpenStreetMap data and a git repository with the additional data,
such as logos, photos, extended descriptions, menu cards and so on.

The following URL paths work:

* `/name-of-place` -> shows the place or business page of a place. This can be also a group of places (branches of a bank for example)
* `/type-of-place` -> shows the top places of this kind, links to the subdivisions
* `/subdivision` -> shows the top places in the subdivision and links to the type base subdivisions
* `/subdivision/type-of-place` -> show the top places but also all other places
* `/n123456/{optional-slug}` -> OSM object with node ID 123456 (same as name-of-place), it redirects to the named place, if it is found in the places list
* `/w123456/{optional-slug}` -> OSM object with way ID, same as above
* `/r123456/{optional-slug}` -> OSM object with relation ID, same as above

All types of pages can contain descriptive text, for example about the area or the type of place as well as a logo.

The place / business page features

* the logo
* the name
* multiple or a single branch with location
* optional: contact form

## Caching

The data from OpenStreetMap and the data repository is cached. If you changed something, wait 5 minute and use Ctrl+F5 to refresh
the necessary page from the source.

## Development

### Local Setup

Clone this repository (`git clone https://github.com/OpenPlaceGuide/opg-pages.git`) and `cd opg-pages`

Clone the data repository and symlink to public assets

```bash
cd storage/app/repositories/
git clone https://github.com/OpenPlaceGuide/data/ opg-data-ethiopia
cd ../../../public/assets
ln -s  ../../storage/app/repositories/opg-data-ethiopia/places ethiopia
```

Install dependencies

```bash
composer install
npm install
```

Open vite

```bash
npm run dev
```

or build with

```bash
npm run build
```

Possible pages:

* /bole/
* /bole/banks
* /nefas-silk/businesses
* /bandira
* /am/bandira
* /zemen-bank

Warning: The root page (/) is currently not existing.

### PHPUnit

```bash
vendor/bin/phpunit
```

PHPunit tests are automatically executed in the GitHub action.

### Cypress

```bash
npx cypress open --e2e --browser chrome
```

## Deployment blended together with osmapp via Docker

This is meant to work together with [OsmApp, OpenPlaceGuide fork](https://github.com/OpenPlaceGuide/osmapp) which runs
on the root path of the page and proxies all other requests to opg-pages.

* build osmapp and Docker-Tag as `osmapp` (in the osmapp folder)

```bash
cd ../osmapp && docker build --build-arg PROXY_BACKEND=http://opg-pages/ . -t osmapp
```

* build this app
```bash
docker build . -t opg-pages
```

Start 
```bash
docker compose up -d
```

Access http://localhost:3000


## Credits

* Parts of Wolfi-PHP based Dockerfile taken from https://github.com/shyim/wolfi-php/
