@props([
    'images' => [],
    'title' => 'Community Street View Images',
    'locationName' => null,
    'branches' => null,
    'containerClass' => 'px-5 py-4 max-w-5xl mx-auto'
])

@if(!empty($images))
    <section class="{{ $containerClass }}">
        <h2 class="section-title mb-4">{{ $title }}</h2>

        <div class="overflow-x-auto flex space-x-4 flex-row w-full mb-6 snap-x pb-2">
            @foreach($images as $image)
                <div class="flex-none snap-start">
                    <figure class="inline-grid grid-cols-1 auto-rows-auto">
                        <a href="{{ $image['mapillary_url'] }}" target="_blank" rel="noopener">
                            <img class="card p-1 md:h-80 h-48 w-auto aspect-video object-cover"
                                 src="{{ $image['large_thumbnail_url'] }}"
                                 alt="Street view image{{ $locationName ? ' from ' . $locationName : ' from Mapillary' }}"
                                 loading="lazy">
                        </a>
                        <figcaption class="py-3 w-0 min-w-full text-sm text-ink/70">
                            <div>
                                {{-- Capture date and photographer --}}
                                @if($image['captured_at_formatted'])
                                    {{ $image['captured_at_formatted'] }}
                                @endif
                                @if($image['creator']['username'])
                                    by {{ $image['creator']['username'] }}
                                @endif

                                <a href="{{ $image['mapillary_url'] }}" target="_blank" rel="noopener">({{ $image['attribution'] }})</a>

                                @if($image['distance_formatted'])
                                    <span class="text-xs text-ink/60">
                                        {{ $image['distance_formatted'] }}
                                        @if(isset($image['branch_key']) && $branches && count($branches) > 1 && isset($image['branch_name']))
                                            away from
                                            <a href="#{{ $image['branch_key'] }}">{{ $image['branch_name'] }}</a>
                                        @elseif($locationName && !isset($image['branch_key']))
                                            from center
                                        @else
                                            away
                                        @endif
                                    </span>
                                @endif

                                @if(isset($image['quality_score']) && $image['quality_score'] > 0)
                                    <span class="text-xs text-star font-semibold">
                                        ★ {{ number_format($image['quality_score'] * 5, 1) }}
                                    </span>
                                @endif
                            </div>
                        </figcaption>
                    </figure>
                </div>
            @endforeach
        </div>

        {{-- Contribute button --}}
        <a href="https://www.mapillary.com/mobile-apps"
           class="btn-quiet mt-2"
           target="_blank">
            <span class="text-sm">Contribute to Mapillary</span>
        </a>
    </section>
@endif
