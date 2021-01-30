<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ config('app.name', 'Laravel') }}</title>

    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>

    <link href="{{ asset('css/app.css') }}" rel="stylesheet"/>
</head>
<body>
<div id="app" class="flex flex-col h-screen justify-between">
    <nav class="bg-header">
        <div class="flex justify-between items-center border-b-2 px-12 py-6">
            <h1 class="text-4xl hover:underline">
                <a class="navbar-brand" href="{{ url('/') }}">{{ config('app.name', 'Laravel') }}</a>
            </h1>
        </div>
    </nav>

    <main class="px-12">
        @yield('content')
    </main>

    <footer>
        <div class="container mx-auto py-6">
            <p class="text-center">Made with 🖤 by <a class="hover:underline" href="https://adrianperez.me" target="_blank">Adrian Perez </a></p>
        </div>
    </footer>
</div>
</body>
</html>
