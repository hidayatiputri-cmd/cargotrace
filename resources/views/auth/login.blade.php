@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <div class="inline-flex w-16 h-16 rounded-2xl bg-gradient-to-tr from-cyan-500 to-blue-600 items-center justify-center text-white shadow-xl shadow-cyan-500/25 mb-4">
            <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <h2 class="text-3xl font-extrabold tracking-tight text-white">CargoTrace Access Portal</h2>
        <p class="mt-2 text-sm text-slate-400">Sign in to monitor multi-modal freight, milestones & exceptions</p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-slate-900/90 py-8 px-6 shadow-2xl rounded-2xl border border-slate-800 sm:px-10">
            <form class="space-y-5" action="{{ route('login') }}" method="POST">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Email Address</label>
                    <div class="mt-1">
                        <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                            class="appearance-none block w-full px-3.5 py-2.5 border border-slate-700 rounded-xl bg-slate-800/80 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm"
                            placeholder="user@cargotrace.test">
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Password</label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="appearance-none block w-full px-3.5 py-2.5 border border-slate-700 rounded-xl bg-slate-800/80 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm"
                            placeholder="••••••••">
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center text-slate-300 cursor-pointer">
                        <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-cyan-600 focus:ring-cyan-500 border-slate-700 rounded bg-slate-800">
                        <span class="ml-2">Remember credentials</span>
                    </label>
                </div>

                <div>
                    <button type="submit"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg shadow-cyan-600/30 text-sm font-bold text-white bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition">
                        Sign In
                    </button>
                </div>
            </form>

            <!-- Quick Demo Login Presets -->
            <div class="mt-8 pt-6 border-t border-slate-800">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 text-center mb-3">Quick Demo One-Click Login</p>
                <div class="grid grid-cols-1 gap-2">
                    <button type="button" onclick="fillDemo('admin@cargotrace.test', 'password')"
                        class="w-full text-left px-3.5 py-2 rounded-xl bg-purple-950/40 hover:bg-purple-900/60 border border-purple-800/50 text-xs flex items-center justify-between transition group">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-400"></span>
                            <div>
                                <span class="font-bold text-purple-300">Administrator</span>
                                <span class="block text-[11px] text-slate-400">admin@cargotrace.test</span>
                            </div>
                        </div>
                        <span class="text-[10px] uppercase font-bold text-purple-300 bg-purple-900/60 px-2 py-0.5 rounded">Fill</span>
                    </button>

                    <button type="button" onclick="fillDemo('operator@cargotrace.test', 'password')"
                        class="w-full text-left px-3.5 py-2 rounded-xl bg-amber-950/40 hover:bg-amber-900/60 border border-amber-800/50 text-xs flex items-center justify-between transition group">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <div>
                                <span class="font-bold text-amber-300">Logistics Operator</span>
                                <span class="block text-[11px] text-slate-400">operator@cargotrace.test</span>
                            </div>
                        </div>
                        <span class="text-[10px] uppercase font-bold text-amber-300 bg-amber-900/60 px-2 py-0.5 rounded">Fill</span>
                    </button>

                    <button type="button" onclick="fillDemo('customer@cargotrace.test', 'password')"
                        class="w-full text-left px-3.5 py-2 rounded-xl bg-cyan-950/40 hover:bg-cyan-900/60 border border-cyan-800/50 text-xs flex items-center justify-between transition group">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-cyan-400"></span>
                            <div>
                                <span class="font-bold text-cyan-300">Customer (Astra Logistik)</span>
                                <span class="block text-[11px] text-slate-400">customer@cargotrace.test</span>
                            </div>
                        </div>
                        <span class="text-[10px] uppercase font-bold text-cyan-300 bg-cyan-900/60 px-2 py-0.5 rounded">Fill</span>
                    </button>

                    <button type="button" onclick="fillDemo('customer2@cargotrace.test', 'password')"
                        class="w-full text-left px-3.5 py-2 rounded-xl bg-slate-800/60 hover:bg-slate-800 border border-slate-700 text-xs flex items-center justify-between transition group">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                            <div>
                                <span class="font-bold text-slate-300">Customer 2 (EuroNordic)</span>
                                <span class="block text-[11px] text-slate-400">customer2@cargotrace.test</span>
                            </div>
                        </div>
                        <span class="text-[10px] uppercase font-bold text-slate-300 bg-slate-700 px-2 py-0.5 rounded">Fill</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function fillDemo(email, pass) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = pass;
    }
</script>
@endsection
