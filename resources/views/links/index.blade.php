@extends ('layouts.app')

@section('content')
    <div class="mt-6 sm:mt-10">
        <section class="flex flex-col bg-white sm:border sm:rounded-md sm:shadow-lg">

            <header class="font-semibold bg-gray-200 text-gray-700 py-5 px-6 sm:py-6 sm:px-8 sm:rounded-t-md">
                My Links
            </header>

            <div class="px-6 py-6 sm:py-8 space-y-6 sm:px-10 sm:space-y-8">
                <div class="flex justify-end items-center mb-6 sm:mb-8">
                    <a href="/links/create"
                       class="inline-block select-none font-bold whitespace-nowrap p-3 rounded-lg text-base leading-normal no-underline text-gray-100 bg-purple-500 hover:bg-purple-700 sm:px-6 transition-colors duration-200">
                        {{ __('Create Link') }}
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 w-full">
                    @foreach($shortLinks as $shortLink)
                        <div class="bg-white border rounded-lg shadow-sm p-6 sm:p-8 flex flex-col justify-between hover:shadow-md transition-shadow duration-200">
                            <div class="space-y-4">
                                <div class="flex justify-between items-start">
                                    <div class="flex flex-col space-y-1 overflow-hidden pr-4">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ __('Code') }}</span>
                                        <a class="text-purple-600 no-underline hover:underline break-all"
                                           href="{{ $shortLink->code }}"
                                           target="_blank">{{ $shortLink->baseUrl().'/'.$shortLink->code }}</a>
                                    </div>
                                    <div class="flex flex-col items-end shrink-0">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ __('Views') }}</span>
                                        <p class="text-gray-600 break-all leading-relaxed line-clamp-3" title="{{ $shortLink->views }}">{{ $shortLink->views }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col space-y-1">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ __('URL') }}</span>
                                    <p class="text-gray-600 break-all leading-relaxed line-clamp-3" title="{{ $shortLink->link }}">{{ $shortLink->link }}</p>
                                </div>
                            </div>
                            <div class="flex justify-end pt-4 mt-4 border-t border-gray-100 space-x-4">
                                <a href="{{ $shortLink->path().'/edit' }}" title="{{ __('Edit') }}" class="text-gray-400 hover:text-purple-600 transition-colors duration-200">
                                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </a>
                                <a href="{{ $shortLink->path().'/delete' }}" title="{{ __('Delete') }}" class="text-gray-400 hover:text-red-500 transition-colors duration-200">
                                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection
