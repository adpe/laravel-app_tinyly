@extends ('layouts.app')

@section('content')
    <main class="mt-6 sm:mt-10">
        <section class="flex flex-col break-words bg-white sm:border-1 sm:rounded-md sm:shadow-sm sm:shadow-lg">

            <header class="font-semibold bg-gray-200 text-gray-700 py-5 px-6 sm:py-6 sm:px-8 sm:rounded-t-md">
                Update Link
            </header>

            <div class="-mt-6 sm:-mt-8 py-6 sm:py-8 space-y-6 sm:space-y-8">
                <form action="{{ $link->path() }}" method="POST" class="w-full px-6 space-y-6 sm:px-10 sm:space-y-8" method="POST">
                    @method('PATCH')
                    @include('links.partials.form', ['buttonText' => 'Update Link'])
                </form>
            </div>
        </section>
    </main>
@endsection
