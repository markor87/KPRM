<div class="flex min-h-screen flex-col items-center justify-center bg-gray-50">
    <div class="w-full max-w-md space-y-8 rounded-lg bg-white p-8 shadow-lg">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">
                Двофакторска аутентификација
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Унесите 6-цифрени код послат на вашу е-пошту
            </p>
        </div>

        <form wire:submit="verify" class="mt-8 space-y-6">
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700">
                    Верификациони код
                </label>
                <input
                    wire:model="code"
                    type="text"
                    id="code"
                    maxlength="6"
                    placeholder="000000"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-center text-2xl tracking-widest"
                    autofocus
                    autocomplete="off"
                >
                @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full rounded-md bg-amber-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
            >
                Верификуј
            </button>

            <div class="text-center">
                <a href="{{ route('filament.admin.auth.login') }}" class="text-sm text-amber-600 hover:text-amber-500">
                    Назад на пријаву
                </a>
            </div>
        </form>
    </div>
</div>
