<form wire:submit.prevent="submit" class="w-full px-6 space-y-6 sm:px-10 sm:space-y-8">
    @csrf
    <div class="flex flex-wrap">
        <label for="email" class="block text-gray-700 text-sm font-bold mb-2 sm:mb-4">
            {{ __('E-Mail Address') }}:
        </label>

        <input wire:model="email" id="email" type="email" name="email"
               class="form-input w-full rounded-md {{ $errors->has('email') ? 'border-red-500' : 'border-gray-300' }}"
               autocomplete="email" autofocus>

        @error('email')
        <p class="text-red-500 text-xs italic mt-4">
            {{ $message }}
        </p>
        @enderror
    </div>

    <div class="flex flex-wrap">
        <label for="password" class="block text-gray-700 text-sm font-bold mb-2 sm:mb-4">
            {{ __('Password') }}:
        </label>

        <input wire:model="password" id="password" type="password" name="password"
               class="form-input w-full rounded-md {{ $errors->has('password') ? 'border-red-500' : 'border-gray-300' }}">

        @error('password')
        <p class="text-red-500 text-xs italic mt-4">
            {{ $message }}
        </p>
        @enderror
    </div>

    <div class="flex items-center">
        <label class="inline-flex items-center text-sm text-gray-700" for="remember">
            <input wire:model="remember" id="remember" type="checkbox" name="remember"
                   class="form-checkbox rounded border-gray-300">
            <span class="ml-2">{{ __('Remember Me') }}</span>
        </label>

        @if (Route::has('password.request'))
            <a class="text-sm text-purple-500 hover:text-purple-700 whitespace-no-wrap no-underline hover:underline ml-auto"
               href="{{ route('password.request') }}">
                {{ __('Forgot Your Password?') }}
            </a>
        @endif
    </div>

    <div class="flex flex-wrap">
        <button type="submit"
                class="w-full select-none font-bold whitespace-no-wrap mb-6 p-3 rounded-lg text-base leading-normal no-underline text-gray-100 bg-purple-500 hover:bg-purple-700 sm:py-4 sm:mb-8">
            {{ __('Login') }}
        </button>

        @if (Route::has('register'))
            <p class="w-full text-xs text-center text-gray-700 mb-6 sm:text-sm sm:mb-8">
                {{ __("Don't have an account?") }}
                <a class="text-purple-500 hover:text-purple-700 no-underline hover:underline" href="{{ route('register') }}">
                    {{ __('Register') }}
                </a>
            </p>
        @endif
    </div>
</form>
