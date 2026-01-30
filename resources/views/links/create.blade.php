@extends ('layouts.app')

@section('content')
    <main class="mt-6 sm:mt-10">
        <section class="flex flex-col break-words bg-white sm:border sm:rounded-md sm:shadow-lg">

            <header class="font-semibold bg-gray-200 text-gray-700 py-5 px-6 sm:py-6 sm:px-8 sm:rounded-t-md">
                {{ $text }}
            </header>

            <div class="-mt-6 sm:-mt-8 py-6 sm:py-8 space-y-6 sm:space-y-8">
                <livewire:link-form :text="$text"/>
            </div>
        </section>
    </main>
@endsection
