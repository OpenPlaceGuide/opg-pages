<x-mapillary-gallery
    :images="$images"
    container-class=""
/>
{{-- Trailing marker keeps the response body non-empty even when there are no
     images, so Laravel's cache.headers middleware still sets the public cache
     headers (it skips empty responses). --}}
<!-- mapillary:{{ count($images) }} -->
