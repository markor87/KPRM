<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'KPRM') }} - Admin Panel</title>

        <!-- Toastify CSS -->
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased bg-gray-100">
        <div class="min-h-screen flex">
            <!-- Sidebar -->
            <div class="w-64 bg-gray-800 shadow-lg flex flex-col">
                <!-- Header -->
                <div class="p-6">
                    <h1 class="text-2xl font-bold text-white">KPRM Admin</h1>
                    <p class="text-gray-400 text-sm mt-1">Panel za upravljanje</p>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 mt-6">
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Povratak na Dashboard
                    </a>

                    <div class="border-t border-gray-700 my-4"></div>

                    <div class="px-6 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Admin Panel
                    </div>

                    @if (auth()->user()->isSuperAdmin() || auth()->user()->can('users.view'))
                        <a href="{{ route('admin.users') }}"
                           class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors {{ request()->routeIs('admin.users') ? 'bg-gray-700 text-white border-l-4 border-blue-500' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            Upravljanje Korisnicima
                        </a>
                    @endif

                    @if (auth()->user()->isSuperAdmin() || auth()->user()->can('groups.view'))
                        <a href="{{ route('admin.groups') }}"
                           class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors {{ request()->routeIs('admin.groups') ? 'bg-gray-700 text-white border-l-4 border-blue-500' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Upravljanje Grupama
                        </a>
                    @endif

                    @if (auth()->user()->isSuperAdmin() || auth()->user()->can('users.view'))
                        <a href="{{ route('admin.assign-users') }}"
                           class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors {{ request()->routeIs('admin.assign-users') ? 'bg-gray-700 text-white border-l-4 border-blue-500' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            Korisnici u Grupama
                        </a>
                    @endif

                    @if (auth()->user()->isSuperAdmin() || auth()->user()->can('permissions.view'))
                        <a href="{{ route('admin.assign-permissions') }}"
                           class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors {{ request()->routeIs('admin.assign-permissions') ? 'bg-gray-700 text-white border-l-4 border-blue-500' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Permisi Grupa
                        </a>
                    @endif

                    <div class="border-t border-gray-700 my-4"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center w-full px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Odjavi se
                        </button>
                    </form>
                </nav>

                <!-- User Info -->
                <div class="p-6 bg-gray-900 border-t border-gray-700 mt-auto">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                            @if (auth()->user()->isSuperAdmin())
                                <span class="inline-flex items-center px-2 py-0.5 mt-1 text-xs font-semibold rounded bg-purple-100 text-purple-800">
                                    Super Admin
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1">
                {{ $slot }}
            </div>
        </div>

        @livewireScripts

        <!-- Alpine.js -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <!-- Toastify JS -->
        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

        <!-- Toast Listener -->
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('toast', (event) => {
                    Toastify({
                        text: event.message,
                        duration: 3000,
                        close: true,
                        gravity: "top",
                        position: "right",
                        stopOnFocus: true,
                        style: {
                            background: event.type === 'error' ? '#ef4444' : event.type === 'success' ? '#10b981' : '#3b82f6',
                        },
                    }).showToast();
                });
            });
        </script>
    </body>
</html>
