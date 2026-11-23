@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-white">Notifications Center</h1>
            <p class="text-xs text-slate-400">Operational alerts, milestone updates, delay alerts & incident notifications</p>
        </div>
        <form action="{{ route('notifications.mark-all-read') }}" method="POST">
            @csrf
            <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300 border border-slate-700 transition">
                Mark All as Read
            </button>
        </form>
    </div>

    <div class="bg-slate-900/90 rounded-2xl border border-slate-800 shadow-xl overflow-hidden divide-y divide-slate-800">
        @forelse($notifications as $n)
            <div class="p-4 sm:p-5 flex items-start justify-between gap-4 {{ $n->read_at ? 'opacity-70 bg-slate-950/40' : 'bg-slate-900/90' }} hover:bg-slate-800/40 transition">
                <div class="flex items-start space-x-3.5">
                    <span class="mt-1 w-2.5 h-2.5 rounded-full shrink-0 {{ $n->read_at ? 'bg-slate-600' : 'bg-cyan-400 animate-pulse' }}"></span>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-xs font-bold text-white">{{ $n->title }}</h3>
                            @if(!$n->read_at)
                                <span class="px-1.5 py-0.2 rounded bg-cyan-950 text-cyan-300 text-[9px] font-bold border border-cyan-800">NEW</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-300 mt-1">{{ $n->message }}</p>
                        <div class="mt-2 flex items-center space-x-3 text-[10px] text-slate-500">
                            <span>{{ $n->created_at->diffForHumans() }}</span>
                            @if($n->shipment)
                                <a href="{{ route('shipments.show', $n->shipment) }}" class="text-cyan-400 hover:underline font-mono">
                                    Shipment #{{ $n->shipment->shipment_number }} &rarr;
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if(!$n->read_at)
                    <form action="{{ route('notifications.read', $n) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800" title="Mark as read">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="py-12 text-center text-xs text-slate-500">
                You have no notifications at this time.
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div>{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
