@extends('layouts.index')

@section('pageTitle')
    Page not found on {{ config('app.name') }}
@endsection

@section('content')
    <header>
        <h1 class="text-3xl px-5 mt-10 md:flex text-center items-center max-w-5xl mx-auto">
            Sorry - Page not found
        </h1>
    </header>
    <section>
        <div class="px-5 py-2 max-w-5xl mx-auto">
            <a href="{{ config('app.url') }}">go to the homepage now</a>
        </div>
    </section>
@stop
