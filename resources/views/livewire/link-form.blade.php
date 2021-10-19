<form wire:submit.prevent="submit" class="w-full px-6 space-y-6 sm:px-8 sm:space-y-8">
    @csrf
    <div class="flex flex-wrap">
        <label for="link" class="block text-gray-700 text-sm font-bold mb-2 sm:mb-4">
            {{ __('URL') }}:
        </label>

        <input wire:model="url" id="url" type="text" class="form-input w-full @error('url')  border-red-500 @enderror"
               name="slug" value="{{ $url ?? old('url') }}" autocomplete="url" autofocus>

        @error('url')
        <p class="text-red-500 text-xs italic mt-4">
            {{ $message }}
        </p>
        @enderror
    </div>

    <div class="flex flex-wrap">
        <label for="code" class="block text-gray-700 text-sm font-bold mb-2 sm:mb-4">
            {{ __('Code') }}:
        </label>

        <input wire:model="code" id="code" type="text" class="form-input w-full @error('code')  border-red-500 @enderror"
               name="code" value="{{ $code ?? old('code') }}" autocomplete="code" autofocus>

        @error('code')
        <p class="text-red-500 text-xs italic mt-4">
            {{ $message }}
        </p>
        @enderror
    </div>

    <div class="flex flex-wrap">
        <button type="submit"
                class="w-full select-none font-bold whitespace-no-wrap p-3 rounded-lg text-base leading-normal no-underline text-gray-100 bg-purple-500 hover:bg-purple-700 sm:py-4">
            {{ $text }}
        </button>
    </div>
</form>
