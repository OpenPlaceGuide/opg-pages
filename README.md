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
