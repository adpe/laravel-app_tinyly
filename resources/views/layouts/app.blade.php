<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ config('app.name', 'Laravel') }}</title>

    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>

    <link href="{{ asset('css/app.css') }}" rel="stylesheet"/>
</head>
<body class="">
<div id="app" class="container mx-auto">
    <nav class="bg-header">
        <div class="flex justify-between items-center border-b-2 py-6">
            <h1 class="text-4xl hover:underline">
                <a class="navbar-brand" href="{{ url('/') }}">{{ config('app.name', 'Laravel') }}</a>
            </h1>
        </div>
    </nav>

    <main class="mt-6">
        @yield('content')
    </main>
</div>
</body>
</html>
