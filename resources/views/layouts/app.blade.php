<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CargoTrace') }} — Global Shipment Visibility & Tracking</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN fallback for guaranteed rendering + Leaflet Map CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            200: '#b9dffe',
                            400: '#38bdf8',
                            500: '#0284c7',
                            600: '#0369a1',
                            700: '#075985',
                            800: '#0c4a6e',
                            900: '#082f49',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .leaflet-popup-content-wrapper, .leaflet-popup-tip {
            background-color: #1e293b;
            color: #f1f5f9;
            border: 1px solid #334155;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body class="h-full flex flex-col bg-slate-950 text-slate-200 antialiased selection:bg-cyan-500 selection:text-white">
    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-50 bg-slate-900/90 backdrop-blur border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand / Logo -->
                <div class="flex items-center space-x-8">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center text-white shadow-lg shadow-cyan-500/20 group-hover:scale-105 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xl font-extrabold tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent">CargoTrace</span>
                            <span class="hidden sm:inline-block text-[10px] uppercase tracking-wider text-cyan-400 font-bold ml-2 px-1.5 py-0.5 rounded bg-cyan-950/60 border border-cyan-800/60">{{ __('messages.app.platform_subtitle') }}</span>
                        </div>
                    </a>

                    <!-- Nav Links -->
                    @auth
                    <nav class="hidden md:flex items-center space-x-1">
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-cyan-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                            {{ __('messages.nav.dashboard') }}
                        </a>
                        <a href="{{ route('shipments.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('shipments.*') ? 'bg-slate-800 text-cyan-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                            {{ __('messages.nav.shipments') }}
                        </a>

                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.carriers.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.carriers.*') ? 'bg-slate-800 text-cyan-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                                {{ __('messages.nav.carriers') }}
                            </a>
                            <a href="{{ route('admin.locations.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.locations.*') ? 'bg-slate-800 text-cyan-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                                {{ __('messages.nav.locations') }}
                            </a>
                            <a href="{{ route('admin.users.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.users.*') ? 'bg-slate-800 text-cyan-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                                {{ __('messages.nav.users') }}
                            </a>
                            <a href="{{ route('admin.audit-logs.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.audit-logs.*') ? 'bg-slate-800 text-cyan-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                                {{ __('messages.nav.audit_logs') }}
                            </a>
                        @endif
                    </nav>
                    @endauth
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center space-x-3">
                    <!-- Language Switcher -->
                    <div class="flex items-center space-x-1 p-1 bg-slate-800/80 rounded-xl border border-slate-700/60 text-xs font-bold" title="{{ __('messages.nav.language') }}">
                        <a href="{{ route('locale.switch', 'en') }}" class="px-2 py-0.5 rounded-lg transition {{ app()->getLocale() === 'en' ? 'bg-cyan-500 text-slate-950 font-extrabold shadow' : 'text-slate-400 hover:text-white' }}" title="{{ __('messages.nav.english') }}">EN</a>
                        <a href="{{ route('locale.switch', 'id') }}" class="px-2 py-0.5 rounded-lg transition {{ app()->getLocale() === 'id' ? 'bg-cyan-500 text-slate-950 font-extrabold shadow' : 'text-slate-400 hover:text-white' }}" title="{{ __('messages.nav.indonesian') }}">ID</a>
                    </div>

                    @auth
                        @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
                            <a href="{{ route('shipments.create') }}" class="hidden sm:inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white shadow-md shadow-cyan-500/20 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>{{ __('messages.nav.new_shipment') }}</span>
                            </a>
                        @endif

                        <!-- Notifications Bell -->
                        @php
                            $unreadCount = auth()->user()->notifications()->unread()->count();
                        @endphp
                        <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition" title="{{ __('messages.nav.notifications') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($unreadCount > 0)
                                <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white animate-pulse">
                                    {{ $unreadCount }}
                                </span>
                            @endif
                        </a>

                        <!-- User Profile Badge & Logout -->
                        <div class="flex items-center space-x-3 pl-3 border-l border-slate-800">
                            <div class="text-right hidden sm:block">
                                <div class="text-xs font-bold text-slate-200">{{ auth()->user()->name }}</div>
                                <div class="text-[10px] uppercase font-semibold">
                                    @if(auth()->user()->isAdmin())
                                        <span class="text-purple-400">{{ __('messages.nav.role_admin') }}</span>
                                    @elseif(auth()->user()->isOperator())
                                        <span class="text-amber-400">{{ __('messages.nav.role_operator') }}</span>
                                    @else
                                        <span class="text-cyan-400">{{ __('messages.nav.role_customer') }}</span>
                                    @endif
                                </div>
                            </div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition" title="{{ __('messages.nav.logout') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg text-sm font-semibold bg-cyan-600 hover:bg-cyan-500 text-white transition">{{ __('messages.nav.sign_in') }}</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Alerts -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
        @if(session('success'))
            <div class="mb-4 flex items-center p-4 bg-emerald-950/60 border border-emerald-800/80 rounded-xl text-emerald-300 text-sm shadow-lg">
                <svg class="w-5 h-5 mr-3 shrink-0 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('warning'))
            <div class="mb-4 flex items-center p-4 bg-amber-950/60 border border-amber-800/80 rounded-xl text-amber-300 text-sm shadow-lg">
                <svg class="w-5 h-5 mr-3 shrink-0 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>{{ session('warning') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 flex items-center p-4 bg-rose-950/60 border border-rose-800/80 rounded-xl text-rose-300 text-sm shadow-lg">
                <svg class="w-5 h-5 mr-3 shrink-0 text-rose-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-950 border-t border-slate-900 py-6 mt-12 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <span class="font-bold text-slate-400">CargoTrace</span> &copy; {{ date('Y') }} — Global Shipment Visibility & Tracking Platform
            </div>
            <div class="flex items-center space-x-4 text-[11px]">
                <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800">Laravel 12 + Sanctum</span>
                <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800">Layered Architecture</span>
                <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800">MySQL 8.0</span>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
