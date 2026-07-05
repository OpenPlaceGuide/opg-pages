@props(['subareas', 'title', 'linkGenerator', 'type' => null])

@if(!empty($subareas))
    <section>
        <div class="px-5 py-2 max-w-5xl mx-auto">
            <h2 class="section-title mb-4">{{ $title }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
                @foreach($subareas as $subarea)
                    <a class="card px-4 py-3 flex flex-row justify-between items-center gap-3"
                       href="{{ $linkGenerator($subarea) }}">
                        <div class="flex-grow">
                            <h3 class="text-base m-0">
                                @if($type)
                                    {{ ucfirst(Fallback::resolve($type->plural)) }} in {{ Fallback::field($subarea->tags, 'name') ?? ucfirst(str_replace('-', ' ', $subarea->slug)) }}
                                @else
                                    {{ Fallback::field($subarea->tags, 'name') ?? ucfirst(str_replace('-', ' ', $subarea->slug)) }}
                                @endif
                            </h3>
                            @if(!$type)
                                @php($description = Fallback::resolve($subarea->descriptions))
                                @if($description)
                                    <p class="text-sm text-ink/70 mt-1">{{ Str::limit($description, 100) }}</p>
                                @endif
                            @endif
                        </div>
                        <div class="flex-shrink-0">
                            @if($type)
                                @php($logo = $type->getLogoUrl())
                                @if($logo)
                                    <span class="relative flex h-10 w-10 shrink-0 mr-4">
                                        <img class="aspect-square h-full w-full" alt="Type Logo" src="{{ $logo }}" />
                                    </span>
                                @else
                                    <span class="relative flex h-8 w-8 shrink-0 mr-4 rounded-full" style="background-color: {{ $subarea->color }};"></span>
                                @endif
                            @else
                                <span class="relative flex h-8 w-8 shrink-0 mr-4 rounded-full" style="background-color: {{ $subarea->color }};"></span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif