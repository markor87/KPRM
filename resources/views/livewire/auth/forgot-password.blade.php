<div class="w-full max-w-md">
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="px-8 pt-10 pb-8">
            <!-- Logo/Brand -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">KPRM</h1>
                <p class="text-gray-600 mt-2">Resetujte vašu lozinku</p>
            </div>

            @if ($emailSent)
                <!-- Success Message -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                Link za resetovanje lozinke je poslat na vaš email!
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <!-- Info Message -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-blue-700">
                        Unesite vašu email adresu i poslaćemo vam link za resetovanje lozinke.
                    </p>
                </div>
            @endif

            <!-- Forgot Password Form -->
            <form wire:submit="sendResetLink" class="space-y-6">
                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email adresa
                    </label>
                    <input
                        type="email"
                        id="email"
                        wire:model="email"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 @error('email') border-red-500 @enderror"
                        placeholder="vas@email.com"
                    >
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                >
                    <span wire:loading.remove wire:target="sendResetLink">Pošalji link</span>
                    <span wire:loading wire:target="sendResetLink">Slanje...</span>
                </button>
            </form>
        </div>

        <!-- Back to Login Link -->
        <div class="px-8 py-6 bg-gray-50 border-t border-gray-200">
            <p class="text-center text-sm text-gray-600">
                Setili ste se lozinke?
                <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                    Vratite se na prijavu
                </a>
            </p>
        </div>
    </div>
</div>
