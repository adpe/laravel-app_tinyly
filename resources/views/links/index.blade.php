@extends ('layouts.app')

@section('content')
    <header class="flex items-center mt-6 mb-6">
        <div class="flex justify-between items-end w-full">
            <h2 class="text-2xl">My Links</h2>
        </div>
    </header>

    <main class="flex flex-col">
        <div class="bg-gray-50 mb-6 py-12 px-16 rounded shadow">
            <form method="POST" action="/generate">
                @csrf
                <div class="field mb-6">
                    <div class="control">
                        <input type="text" name="link"
                               class="flex-1 appearance-none border border-transparent w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-md rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-gray-600 focus:border-transparent"
                               placeholder="Enter URL">
                    </div>
                </div>

                <div class="field mb-6">
                    <input type="text" name="code"
                           class="flex-1 appearance-none border border-transparent w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-md rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-gray-600 focus:border-transparent"
                           placeholder="Enter Code">
                </div>

                <div class="field">
                    <button type="submit"
                            class="flex-shrink-0 bg-gray-600 text-white text-base font-semibold w-full py-2 px-4 rounded-lg shadow-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:ring-offset-gray-200">
                        Create Link
                    </button>
                </div>
            </form>

            @if (Session::has('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 mt-6 px-4 py-3 rounded relative"
                     role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline">{{ Session::get('success') }}</span>
                </div>
            @endif
        </div>
        <div class="bg-gray-50 py-12 px-16 rounded shadow">
            <table class="table-auto bg-white w-full rounded shadow">
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
    </main>
@endsection
