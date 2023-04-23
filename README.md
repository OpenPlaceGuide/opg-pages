# OpenPlaceGuide Pages (opg-pages)

*WIP*

License: Undecided, please contact us if you are interested to use any of this.

## Stack

* Laravel 10
* PHP 8.1

## Symlinking repository

* Checkout data repository to storage/app/repositories, for example https://github.com/OpenPlaceGuide/data
* Link to assets
* ```opg-pages/public/assets$ ls -al
  ethiopia -> ../../storage/app/repositories/opg-data-ethiopia/places
  ```

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

### Installation

** Draft - not sure if necessary **

To make Amharic transliteration work (similar steps might be required for your local language)

```sudo locale-gen am_ET && sudo locale-gen am_ET.UTF-8 && sudo update-locale```
