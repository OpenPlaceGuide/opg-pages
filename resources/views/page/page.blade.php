<img height="100" src="{{ asset($logo) }} ">

{{ $main->tags->name }}

@foreach($branches as $branch)
    <li>{{ $branch->tags->name ?? 'no default name' }}</li>
@endforeach
