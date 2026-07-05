@props([
    'reviews' => [],
    'title' => 'Community Reviews',
    'branches' => null,
    'containerClass' => 'px-5 py-4 max-w-5xl mx-auto'
])

<section class="{{ $containerClass }}">
    <h2 class="text-xl font-bold mb-4">{{ $title }}</h2>

@if(!empty($reviews))
        <div class="space-y-6">
            @foreach($reviews as $review)
                <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
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
                                <h4 class="font-medium text-gray-900 mr-3">{{ $reviewerName }}</h4>
                                @if(isset($review['metadata']['is_affiliated']) && $review['metadata']['is_affiliated'] === 'true')
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">Affiliated</span>
                                @endif
                            </div>

                            {{-- Star rating --}}
                            <div class="flex items-center mb-2">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= floor($review['star_rating']))
                                        <svg class="w-5 h-5 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                        </svg>
                                    @elseif($i <= $review['star_rating'])
                                        <svg class="w-5 h-5 text-yellow-400" viewBox="0 0 20 20">
                                            <defs>
                                                <linearGradient id="half-star-{{ $review['id'] }}-{{ $i }}">
                                                    <stop offset="50%" stop-color="#fbbf24"/>
                                                    <stop offset="50%" stop-color="#e5e7eb"/>
                                                </linearGradient>
                                            </defs>
                                            <path fill="url(#half-star-{{ $review['id'] }}-{{ $i }})" d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-gray-300 fill-current" viewBox="0 0 20 20">
                                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                        </svg>
                                    @endif
                                @endfor
                                <span class="ml-2 text-sm text-gray-600">{{ $review['star_rating'] }}/5</span>
                            </div>

                            {{-- Location info and review type --}}
                            @if(isset($review['subject']) && is_array($review['subject']) && isset($review['subject']['name']) && !empty($review['subject']['name']))
                                <h3 class="font-semibold text-lg text-gray-900 mb-2">{{ $review['subject']['name'] }}</h3>
                            @endif

                            {{-- Review type indicator --}}
                            @if(isset($review['match_type']))
                                <div class="mb-2">
                                    @if($review['match_type'] === 'location')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            📍 Location Review
                                        </span>
                                        @if(isset($review['branch_name']) && $branches && count($branches) > 1)
                                            <span class="ml-2 text-sm text-gray-600">
                                                for <a href="#{{ $review['branch_key'] }}" class="text-blue-600 hover:underline">{{ $review['branch_name'] }}</a>
                                            </span>
                                        @endif
                                    @elseif($review['match_type'] === 'company_name')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            🏢 Company Review
                                        </span>
                                    @elseif($review['match_type'] === 'company_mention')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            💬 Mentions Company
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Date --}}
                        @if($review['created_at_formatted'])
                            <div class="text-sm text-gray-500">
                                {{ $review['created_at_formatted'] }}
                            </div>
                        @endif
                    </div>

                    {{-- Review text --}}
                    @if(!empty($review['opinion']))
                        <div class="mb-4">
                            <p class="text-gray-800 leading-relaxed">{{ $review['opinion'] }}</p>
                        </div>
                    @endif

                    {{-- Review images --}}
                    @if(!empty($review['images']))
                        <div class="mb-4">
                            <div class="overflow-x-auto flex space-x-4 flex-row w-full">
                                @foreach($review['images'] as $image)
                                    <div class="flex-none">
                                        <a href="{{ $image['url'] }}" target="_blank" rel="noopener" class="block hover:opacity-90 transition-opacity">
                                            <img class="shadow-lg p-1 bg-white md:h-80 h-48 w-auto cursor-pointer"
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
                    <div class="flex items-center justify-between text-xs text-gray-500 pt-4 border-t border-gray-100">
                        <div class="flex items-center space-x-4">
                            @if(isset($review['metadata']['experience_context']))
                                <span class="capitalize">{{ str_replace('_', ' ', $review['metadata']['experience_context']) }}</span>
                            @endif
                        </div>

                        <div>
                            <a href="https://mangrove.reviews" target="_blank" rel="noopener" class="text-green-600 hover:underline">
                                Mangrove Reviews
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
@else
    <p class="text-gray-500 text-center py-8">No reviews found for this place, yet. Be the first to write a review!</p>
@endif
</section>
