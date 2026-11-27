@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <div class="inline-flex w-16 h-16 rounded-2xl bg-gradient-to-tr from-pink-500 via-fuchsia-500 to-rose-600 items-center justify-center text-white shadow-xl shadow-pink-500/25 mb-4">
            <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <h2 class="text-3xl font-extrabold tracking-tight text-white">CargoTrace Access Portal</h2>
        <p class="mt-2 text-sm text-slate-400">Sign in to monitor multi-modal freight, milestones & exceptions</p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-[#1c1236] py-8 px-6 shadow-2xl rounded-2xl border border-[#33215c] sm:px-10">
            <form class="space-y-5" action="{{ route('login') }}" method="POST">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Email Address</label>
                    <div class="mt-1">
                        <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                            class="appearance-none block w-full px-3.5 py-2.5 border border-[#33215c] rounded-xl bg-[#120b24] text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500 text-sm"
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
                            class="appearance-none block w-full px-3.5 py-2.5 border border-[#33215c] rounded-xl bg-[#120b24] text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500 text-sm"
                            placeholder="••••••••">
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center text-slate-300 cursor-pointer">
                        <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-[#33215c] rounded bg-[#120b24]">
                        <span class="ml-2">Remember credentials</span>
                    </label>
                </div>

                <div>
                    <button type="submit"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg shadow-pink-600/30 text-sm font-bold text-white bg-gradient-to-r from-pink-500 via-fuchsia-500 to-rose-600 hover:from-pink-400 hover:to-rose-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition">
                        Sign In
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
