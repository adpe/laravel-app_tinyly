@extends('layouts.app')

@section('content')
<main class="mt-6 sm:container sm:mx-auto sm:max-w-lg sm:mt-10">
    <div class="flex">
        <div class="w-full">
            <section class="flex flex-col break-words bg-white sm:border sm:rounded-md sm:shadow-lg">

                <header class="font-semibold bg-gray-200 text-gray-700 py-5 px-6 sm:py-6 sm:px-8 sm:rounded-t-md">
                    {{ __('Login') }}
                </header>

                <livewire:login-form />
            </section>
        </div>
    </div>
</main>
@endsection
