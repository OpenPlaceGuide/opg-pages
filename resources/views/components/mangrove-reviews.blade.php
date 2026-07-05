@props([
    'reviews' => [],
    'title' => 'Community Reviews',
    'branches' => null,
    'containerClass' => 'px-5 py-4 max-w-5xl mx-auto'
])

<section class="{{ $containerClass }}">
    <h2 class="section-title mb-4">{{ $title }}</h2>

@if(!empty($reviews))
        <div class="space-y-6">
            @foreach($reviews as $review)
                <div class="card p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            {{-- Reviewer name --}}
                            @php
                                $reviewerName = 'Anonymous';
                                if (isset($review['metadata']['nickname']) && !empty($review['metadata']['nickname'])) {
                                    $reviewerName = $review['metadata']['nickname'];
                                }
                            @endphp
                            <div class="flex items-center mb-2">
                                <h4 class="font-medium mr-3">{{ $reviewerName }}</h4>
                                @if(isset($review['metadata']['is_affiliated']) && $review['metadata']['is_affiliated'] === 'true')
                                    <span class="chip text-xs">Affiliated</span>
                                @endif
                            </div>

                            {{-- Star rating --}}
                            <div class="flex items-center mb-2">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= floor($review['star_rating']))
                                        <svg class="w-5 h-5 text-star fill-current" viewBox="0 0 20 20">
                                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                        </svg>
                                    @elseif($i <= $review['star_rating'])
                                        <svg class="w-5 h-5 text-star" viewBox="0 0 20 20">
                                            <defs>
                                                <linearGradient id="half-star-{{ $review['id'] }}-{{ $i }}">
                                                    <stop offset="50%" style="stop-color: rgb(var(--star))"/>
                                                    <stop offset="50%" style="stop-color: rgb(var(--edge))"/>
                                                </linearGradient>
                                            </defs>
                                            <path fill="url(#half-star-{{ $review['id'] }}-{{ $i }})" d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-edge fill-current" viewBox="0 0 20 20">
                                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                        </svg>
                                    @endif
                                @endfor
                                <span class="ml-2 text-sm text-ink/70">{{ $review['star_rating'] }}/5</span>
                            </div>

                            {{-- Location info and review type --}}
                            @if(isset($review['subject']) && is_array($review['subject']) && isset($review['subject']['name']) && !empty($review['subject']['name']))
                                <h3 class="font-semibold text-lg mb-2">{{ $review['subject']['name'] }}</h3>
                            @endif

                            {{-- Review type indicator --}}
                            @if(isset($review['match_type']))
                                <div class="mb-2">
                                    @if($review['match_type'] === 'location')
                                        <span class="chip text-xs font-medium">
                                            📍 Location Review
                                        </span>
                                        @if(isset($review['branch_name']) && $branches && count($branches) > 1)
                                            <span class="ml-2 text-sm text-ink/70">
                                                for <a href="#{{ $review['branch_key'] }}">{{ $review['branch_name'] }}</a>
                                            </span>
                                        @endif
                                    @elseif($review['match_type'] === 'company_name')
                                        <span class="chip text-xs font-medium">
                                            🏢 Company Review
                                        </span>
                                    @elseif($review['match_type'] === 'company_mention')
                                        <span class="chip text-xs font-medium">
                                            💬 Mentions Company
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Date --}}
                        @if($review['created_at_formatted'])
                            <div class="text-sm text-ink/60">
                                {{ $review['created_at_formatted'] }}
                            </div>
                        @endif
                    </div>

                    {{-- Review text --}}
                    @if(!empty($review['opinion']))
                        <div class="mb-4">
                            <p class="leading-relaxed">{{ $review['opinion'] }}</p>
                        </div>
                    @endif

                    {{-- Review images --}}
                    @if(!empty($review['images']))
                        <div class="mb-4">
                            <div class="overflow-x-auto flex space-x-4 flex-row w-full">
                                @foreach($review['images'] as $image)
                                    <div class="flex-none">
                                        <a href="{{ $image['url'] }}" target="_blank" rel="noopener" class="block hover:opacity-90 transition-opacity">
                                            <img class="card p-1 md:h-80 h-48 w-auto cursor-pointer"
                                                 src="{{ $image['url'] }}"
                                                 alt="{{ $image['alt'] }}"
                                                 loading="lazy"
                                                 title="Click to view full resolution">
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Metadata --}}
                    <div class="flex items-center justify-between text-xs text-ink/60 pt-4 border-t-2 border-edge">
                        <div class="flex items-center space-x-4">
                            @if(isset($review['metadata']['experience_context']))
                                <span class="capitalize">{{ str_replace('_', ' ', $review['metadata']['experience_context']) }}</span>
                            @endif
                        </div>

                        <div>
                            <a href="https://mangrove.reviews" target="_blank" rel="noopener">
                                Mangrove Reviews
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
@else
    <p class="text-ink/60 text-center py-8">No reviews found for this place, yet. Be the first to write a review!</p>
@endif
</section>
