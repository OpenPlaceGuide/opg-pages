## Overpass Queries

### Populate `place.yaml` with branches

You can obtain the query from the laravel.log

```overpass
[out:json][timeout:25];

area(3601707699)->.searchArea;

nwr["amenity"="bank"]["name"~"commercial bank", i](area.searchArea);

out center;
```

-> pass trough xapiJson2yaml.php

-> get a list of nodes for this bank
