@extends('layouts.app')

@section('content')
    <div class="flex min-h-screen">
        @include('partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('partials.topbar')

            <main class="flex-1 px-6 py-8 md:px-10">
                @if (session('status'))
                    <div class="aurex-badge mb-6">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('dashboard')
            </main>
        </div>
    </div>
@endsection
