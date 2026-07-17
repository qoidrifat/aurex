@extends('layouts.app')

@section('content')
    @include('partials.nav')

    <main>
        @yield('marketing')
    </main>

    @include('partials.footer')
@endsection
