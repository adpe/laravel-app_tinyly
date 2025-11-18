@extends ('layouts.app')

@section('content')
    <main class="mt-6 sm:mt-10">
        <section class="flex flex-col wrap-break-word bg-white sm:border sm:rounded-md sm:shadow-xs sm:shadow-lg">

            <header class="font-semibold bg-gray-200 text-gray-700 py-5 px-6 sm:py-6 sm:px-8 sm:rounded-t-md">
                My Links
            </header>

            <div class="px-6 py-6 sm:py-8 space-y-6 sm:px-10 sm:space-y-8">
                <div class="flex flex-wrap mb-8 float-right">
                    <a href="/links/create"
                       class="select-none font-bold whitespace-no-wrap p-3 rounded-lg text-base leading-normal no-underline text-gray-100 bg-purple-500 hover:bg-purple-700 sm:py-4">Create
                        Link</a>
                </div>

                <table class="table-auto bg-white w-full">
                    <thead>
                    <tr>
                        <th class="bg-gray-100 border text-left px-4 py-2">{{ __('Code') }}</th>
                        <th class="bg-gray-100 border text-left px-4 py-2">{{ __('URL') }}</th>
                        <th class="bg-gray-100 border text-left px-4 py-2">{{ __('Views') }}</th>
                        <th class="bg-gray-100 border text-left px-4 py-2">{{ __('Actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($shortLinks as $shortLink)
                        <tr>
                            <td class="border px-4 py-2"><a class="no-underline hover:underline"
                                                            href="{{ $shortLink->code }}"
                                                            target="_blank">{{ $shortLink->baseUrl().'/'.$shortLink->code }}</a>
                            </td>
                            <td class="border px-4 py-2">{{ $shortLink->link }}</td>
                            <td class="border px-4 py-2">{{ $shortLink->views }}</td>
                            <td class="border px-4 py-2">
                                <span class="flex">
                                    <a href="{{ $shortLink->path().'/edit' }}" class="mr-3">
                                        <svg class="h-6 w-6 text-gray-500 hover:text-purple-500" xmlns="http://www.w3.org/2000/svg"
                                             fill="none"
                                             viewBox="0 0 24 24"
                                             stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ $shortLink->path().'/delete' }}">
                                        <svg class="h-6 w-6 text-gray-500 hover:text-purple-500" xmlns="http://www.w3.org/2000/svg"
                                             fill="none"
                                             viewBox="0 0 24 24"
                                             stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </a>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </main>
@endsection
