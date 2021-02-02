@extends ('layouts.app')

@section('content')
<main class="sm:mt-10">
    <section class="flex flex-col break-words bg-white sm:border-1 sm:rounded-md sm:shadow-sm sm:shadow-lg">

        <header class="font-semibold bg-gray-200 text-gray-700 py-5 px-6 sm:py-6 sm:px-8 sm:rounded-t-md">
            My Links
        </header>

        <form class="w-full px-6 space-y-6 sm:px-10 sm:space-y-8" method="POST"
              action="{{ route('generate') }}">
            @csrf

            <div class="flex flex-wrap">
                <label for="link" class="block text-gray-700 text-sm font-bold mb-2 sm:mb-4">
                    {{ __('URL') }}:
                </label>

                <input id="link" type="text" class="form-input w-full @error('link')  border-red-500 @enderror"
                       name="link" value="{{ old('link') }}" required autocomplete="link" autofocus>

                @error('link')
                <p class="text-red-500 text-xs italic mt-4">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <div class="flex flex-wrap">
                <label for="code" class="block text-gray-700 text-sm font-bold mb-2 sm:mb-4">
                    {{ __('Code') }}:
                </label>

                <input id="code" type="text" class="form-input w-full @error('code')  border-red-500 @enderror"
                       name="code" value="{{ old('code') }}" required autocomplete="code" autofocus>

                @error('code')
                <p class="text-red-500 text-xs italic mt-4">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <div class="flex flex-wrap">
                <button type="submit"
                        class="w-full select-none font-bold whitespace-no-wrap p-3 rounded-lg text-base leading-normal no-underline text-gray-100 bg-purple-500 hover:bg-purple-700 sm:py-4">
                    Create Link
                </button>
            </div>

            @if (Session::has('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 mt-6 px-4 py-3 rounded relative"
                     role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline">{{ Session::get('success') }}</span>
                </div>
            @endif
        </form>

        <div class="px-6 py-6 sm:py-8 space-y-6 sm:px-10 sm:space-y-8">
            <table class="table-auto bg-white w-full">
                <thead>
                <tr>
                    <th class="bg-gray-100 border text-left px-4 py-2">#</th>
                    <th class="bg-gray-100 border text-left px-4 py-2">Source Link</th>
                    <th class="bg-gray-100 border text-left px-4 py-2">Destination Link</th>
                </tr>
                </thead>
                <tbody>
                @foreach($shortLinks as $shortLink)
                    <tr>
                        <td class="border px-4 py-2">{{ $shortLink->id }}</td>
                        <td class="border px-4 py-2"><a class="no-underline hover:underline"
                                                        href="{{ $shortLink->code }}"
                                                        target="_blank">{{ Request::url() . '/' . $shortLink->code }}</a>
                        </td>
                        <td class="border px-4 py-2">{{ $shortLink->link }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection
