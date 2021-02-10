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
        {{ $buttonText }}
    </button>
</div>
