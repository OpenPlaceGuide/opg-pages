@if(!empty($reviews))
    <x-mangrove-reviews
        :reviews="$reviews"
        title="Reviews"
        container-class=""
    />
@endif
{{-- Trailing marker keeps the response body non-empty even when there are no
     reviews, so Laravel's cache.headers middleware still sets the public cache
     headers (it skips empty responses). --}}
<!-- mangrove:{{ count($reviews) }} -->

