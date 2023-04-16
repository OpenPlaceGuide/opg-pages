{{ ucfirst($type->getPlural()) }} in XYZ

<h2>Location(s)</h2>
@foreach($places as $place)
    <li>{{ $place->tags->name ?? 'no name' }}</li>
@endforeach
