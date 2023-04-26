@extends('layouts.index')

@section('content')
    <style>
        /* Animate the fade out effect */
        @keyframes fade-out {
            from {
                background-color: yellowgreen;
            }
            to {
                background-color: transparent;
            }
        }

        /* Apply the fade out animation to the targeted section */
        section:target {
            animation-name: fade-out;
            animation-duration: 2s; /* Change this value to adjust the animation speed */
            animation-timing-function: ease-out;
        }
    </style>

    <h1 class="text-3xl">
        @if($logoUrl)
            <img class="h-20  mr-5 inline aspect-square" src="{{ asset($logoUrl) }} ">
        @endif

        {{ Fallback::field($main->tags, 'name') }}
    </h1>

    @if($newPlaceUrl)
        <a href="{{ $newPlaceUrl }}">Create short URL / start contributing</a>
    @endif

    <h2>Gallery</h2>

    <div class="flex-shrink-0">
        <div x-data="photoGalleryApp" class="max-w-xl flex flex-col">
            <div class="flex items-center sm:h-80">
                <div :class="{'cursor-not-allowed opacity-50': ! hasPrevious()}"  class="hidden sm:block cursor-pointer">
                    <svg version="1.0" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg" fill="currentColor" stroke="currentColor" class="h-8" x-on:click="previousPhoto()">
                        <path d="m42.166 55.31-24.332-25.31 24.332-25.31v50.62z" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.125"/>
                    </svg>
                </div>
                <div class="w-full sm:w-108 flex justify-center">
                    <img x-ref="mainImage" class="w-full sm:w-auto sm:h-80" src="" loading="lazy" />
                </div>
                <div :class="{'cursor-not-allowed opacity-50': ! hasNext()}"  class="hidden sm:block cursor-pointer">
                    <svg version="1.0" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg" fill="currentColor" stroke="currentColor" class="h-8" x-on:click="nextPhoto()">
                        <path d="m17.834 55.31 24.332-25.31-24.332-25.31v50.62z" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.125"/>
                    </svg>
                </div>
            </div>
            <div class="flex justify-center mt-1 space-x-1">
                @php $counter = 0 @endphp
                @foreach($gallery as $text => $mediaPath)
                    <img src="{{ asset($mediaPath) }}" :class="{'ring-2 opacity-50': currentPhoto == {{ $counter }}}" class="h-16 w-16" x-on:click="pickPhoto({{ $counter }})">
                    <p>{{ $text }}</p>
                    @php $counter++ @endphp
            @endforeach
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('photoGalleryApp', () => ({
                currentPhoto: 0,
                photos: {!! json_encode(array_values($gallery)) !!},
                init() { this.changePhoto(); },
                nextPhoto() {
                    if ( this.hasNext() ) {
                        this.currentPhoto++;
                        this.changePhoto();
                    }
                },
                previousPhoto() {
                    if ( this.hasPrevious() ) {
                        this.currentPhoto--;
                        this.changePhoto();
                    }
                },
                changePhoto() {
                    this.$refs.mainImage.src = this.photos[this.currentPhoto];
                },
                pickPhoto(index) {
                    this.currentPhoto = index;
                    this.changePhoto();
                },
                hasPrevious() {
                    return this.currentPhoto > 0;
                },
                hasNext() {
                    return this.photos.length > (this.currentPhoto + 1);
                }
            }))
        })
    </script>

    <h2>Location(s)</h2>
    @foreach($branches as $branch)
        <section id="{{ $branch->idInfo->getKey() }}">
            <h3>{{ Fallback::field($branch->tags, 'name') }}</h3>

            <img src="{{ route('tripleZoomMap', ['lat' => $branch->lat, 'lon' => $branch->lon, 'slug' => \App\Services\Language::slug(Fallback::field($branch->tags, 'name', language: 'en')), 'text' => Fallback::field($branch->tags, 'name')]) }}">
            <ul>
                <li><a href="{{ $branch->idInfo->getOsmUrl() }}" target="_blank">OSM Info</a></li>
                <li><a href="{{ $branch->idInfo->getOsmUrl('https://osmapp.org') }}" target="_blank">OSM App</a></li>
            </ul>
        </section>
    @endforeach
@stop
