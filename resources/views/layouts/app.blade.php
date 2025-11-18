<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts + Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <livewire:styles />
</head>
<body class="bg-gray-100 h-screen antialiased leading-none font-sans">
<div id="app" class="flex flex-col h-screen">
    <header class="bg-gray-800 py-6">
        <div class="container mx-auto flex justify-between items-center px-6">
            <div>
                <a href="{{ url('/') }}" class="text-lg font-semibold text-gray-100 no-underline">
                    {{ config('app.name', 'Laravel') }}
                </a>
            </div>
            <nav class="space-x-4 text-gray-300 text-sm sm:text-base">
                @guest
                    <a class="no-underline hover:underline" href="{{ route('login') }}">{{ __('Login') }}</a>
                    @if (Route::has('register'))
                        <a class="no-underline hover:underline" href="{{ route('register') }}">{{ __('Register') }}</a>
                    @endif
                @else
                    <a href="/links" class="no-underline hover:underline">{{ Auth::user()->name }}</a>

                    <a href="{{ route('logout') }}"
                       class="no-underline hover:underline"
                       onclick="event.preventDefault();
                                document.getElementById('logout-form').submit();">{{ __('Logout') }}</a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                        {{ csrf_field() }}
                    </form>
                @endguest
            </nav>
        </div>
    </header>

    <main class="container mx-auto mb-auto px-6">
        @yield('content')
    </main>

    @if (session('success_message'))
        <div class="alert-toast fixed bottom-0 right-0 m-8 w-5/6 md:w-full max-w-sm">
            <input type="checkbox" class="hidden" id="notification">

            <label
                class="close cursor-pointer flex items-start justify-between w-full p-2 bg-green-500 h-8 rounded-sm shadow-lg text-white"
                title="close" for="notification">
                <span class="block sm:inline"><strong class="font-bold">Success!</strong> {{ session()->get('success_message') }}</span>

                <svg class="fill-current text-white" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                     viewBox="0 0 18 18">
                    <path
                        d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                </svg>
            </label>
        </div>
    @endif

    @if (session('error_message'))
        <div class="alert-toast fixed bottom-0 right-0 m-8 w-5/6 md:w-full max-w-sm">
            <input type="checkbox" class="hidden" id="notification">

            <label
                class="close cursor-pointer flex items-start justify-between w-full p-2 bg-red-500 h-8 rounded-sm shadow-lg text-white"
                title="close" for="notification">
                <span class="block sm:inline"><strong class="font-bold">Error!</strong> {{ session()->get('error_message') }}</span>

                <svg class="fill-current text-white" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                     viewBox="0 0 18 18">
                    <path
                        d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                </svg>
            </label>
        </div>
    @endif

    <footer class="container mx-auto py-6">
        <p class="text-center">Made with 🖤 by
            <a class="hover:underline" href="https://adrianperez.me"
               target="_blank">Adrian Perez </a>
        </p>
    </footer>
</div>
<livewire:scripts />
</body>
</html>
