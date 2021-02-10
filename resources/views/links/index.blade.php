@extends ('layouts.app')

@section('content')
    <main class="sm:mt-10">
        <section class="flex flex-col break-words bg-white sm:border-1 sm:rounded-md sm:shadow-sm sm:shadow-lg">

            <header class="font-semibold bg-gray-200 text-gray-700 py-5 px-6 sm:py-6 sm:px-8 sm:rounded-t-md">
                My Links
            </header>

            <div class="px-6 py-6 sm:py-8 space-y-6 sm:px-10 sm:space-y-8">
                <div class="flex flex-wrap mb-8 float-right">
                    <a href="/links/create" class="select-none font-bold whitespace-no-wrap p-3 rounded-lg text-base leading-normal no-underline text-gray-100 bg-purple-500 hover:bg-purple-700 sm:py-4">Create Link</a>
                </div>

                <table class="table-auto bg-white w-full">
                    <thead>
                    <tr>
                        <th class="bg-gray-100 border text-left px-4 py-2">{{ __('Code') }}</th>
                        <th class="bg-gray-100 border text-left px-4 py-2">{{ __('URL') }}</th>
                        <th class="bg-gray-100 border text-left px-4 py-2">{{ __('Actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($shortLinks as $shortLink)
                        <tr>
                            <td class="border px-4 py-2"><a class="no-underline hover:underline"
                                                            href="{{ $shortLink->code }}"
                                                            target="_blank">{{ Request::url() . '/' . $shortLink->code }}</a>
                            </td>
                            <td class="border px-4 py-2">{{ $shortLink->link }}</td>
                            <td class="border px-4 py-2"><a href="{{ $shortLink->path().$shortLink->id.'/delete' }}"
                                                            class="button">{{ __('Delete') }}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </main>
@endsection
