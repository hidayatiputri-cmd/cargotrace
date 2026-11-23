@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <a href="{{ route('shipments.index') }}" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-black font-mono text-white tracking-tight">{{ $shipment->shipment_number }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider
                        @if($shipment->current_status === 'delivered') bg-emerald-950 text-emerald-400 border border-emerald-800
                        @elseif($shipment->current_status === 'exception' || $shipment->is_delayed) bg-rose-950 text-rose-400 border border-rose-800
                        @elseif($shipment->current_status === 'customs_hold') bg-amber-950 text-amber-400 border border-amber-800
                        @elseif($shipment->current_status === 'picked_up') bg-indigo-950 text-indigo-400 border border-indigo-800
                        @elseif($shipment->current_status === 'booked') bg-blue-950 text-blue-400 border border-blue-800
                        @else bg-slate-800 text-slate-300 border border-slate-700 @endif">
                        {{ str_replace('_', ' ', $shipment->current_status) }}
                    </span>
                    @if(auth()->user()->isCustomer())
                        <span class="text-[10px] px-2 py-0.5 rounded bg-cyan-950 text-cyan-300 border border-cyan-800 font-semibold">
                            Consignee Account View
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-400 mt-0.5">
                    Carrier: <span class="font-bold text-slate-200">{{ $shipment->carrier->name }} ({{ $shipment->carrier->code }})</span> &bull;
                    Customer: <span class="font-bold text-slate-200">{{ $shipment->customer->name }}</span>
                </p>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
                <button type="button" onclick="document.getElementById('eventModal').classList.remove('hidden')"
                    class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg shadow-cyan-600/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Add Tracking Event</span>
                </button>

                <button type="button" onclick="document.getElementById('exceptionModal').classList.remove('hidden')"
                    class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-rose-600/80 hover:bg-rose-500 text-white font-bold text-xs shadow transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Log Exception</span>
                </button>

                <a href="{{ route('shipments.edit', $shipment) }}" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition" title="Edit Shipment">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
            @endif
        </div>
    </div>

    <!-- Delay Alert Banner (if delayed) -->
    @if($shipment->is_delayed)
        <div class="p-4 rounded-2xl bg-rose-950/70 border border-rose-800 shadow-xl flex items-start space-x-3 text-rose-200 text-xs">
            <svg class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-rose-300 text-sm">Shipment Schedule Exception — Delay Detected</span>
                    <span class="px-2 py-0.5 rounded uppercase font-bold text-[10px] bg-rose-900 text-rose-200 border border-rose-700">Severity: {{ $shipment->delay_severity }}</span>
                </div>
                <p class="mt-1 text-slate-300">
                    Current estimated transit delay is <strong class="text-rose-400">+{{ $shipment->delay_hours }} hours</strong> behind contractual schedule.
                    Revised ETA is <strong>{{ $shipment->estimated_arrival?->format('l, F j, Y — H:i T') }}</strong> (originally expected {{ $shipment->expected_arrival->format('M d, H:i') }}).
                </p>
            </div>
        </div>
    @endif

    <!-- Lifecycle Progress Bar: CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> DELIVERED -->
    @php
        $statusOrder = ['created', 'booked', 'picked_up', 'in_transit', 'delivered'];
        $currentIdx = array_search(strtolower($shipment->current_status), $statusOrder, true);
        if ($currentIdx === false) {
            // Exceptions or holds usually occur while in transit
            $currentIdx = 3;
        }
    @endphp
    <div class="bg-slate-900/90 p-5 rounded-2xl border border-slate-800 shadow-xl">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Shipment Lifecycle Progression</span>
            <span class="text-[11px] font-mono text-cyan-400">Stage {{ $currentIdx + 1 }} of 5</span>
        </div>

        <div class="grid grid-cols-5 gap-2 relative">
            @foreach(['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'] as $idx => $stageName)
                @php
                    $isPassed = $idx < $currentIdx;
                    $isCurrent = $idx === $currentIdx;
                @endphp
                <div class="flex flex-col items-center text-center">
                    <div class="w-full flex items-center justify-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs transition shadow
                            @if($isPassed) bg-emerald-600 text-white
                            @elseif($isCurrent)
                                @if($shipment->current_status === 'delivered') bg-emerald-500 text-white ring-4 ring-emerald-500/30
                                @elseif($shipment->current_status === 'exception' || $shipment->is_delayed) bg-rose-600 text-white ring-4 ring-rose-500/30
                                @else bg-cyan-500 text-white ring-4 ring-cyan-500/30 animate-pulse @endif
                            @else bg-slate-800 text-slate-500 border border-slate-700 @endif">
                            @if($isPassed)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            @else
                                {{ $idx + 1 }}
                            @endif
                        </div>
                    </div>
                    <div class="mt-2 text-[10px] font-bold tracking-wider uppercase
                        @if($isCurrent) text-cyan-400
                        @elseif($isPassed) text-emerald-400
                        @else text-slate-500 @endif">
                        {{ str_replace('_', ' ', $stageName) }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Current Valid Location & Key Summary Grid -->
    @php
        $latestLocationEv = $shipment->latestLocationEvent();
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Current Location Card (High Priority) -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-950 p-4 rounded-2xl border border-cyan-800/60 shadow-xl relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold text-cyan-400 tracking-wider flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
                    <span>Current Live Location</span>
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded bg-cyan-950 text-cyan-300 border border-cyan-800 font-semibold">
                    @if($latestLocationEv?->location) Terminal Hub @elseif($latestLocationEv?->hasCoordinates()) GPS Telemetry @else Initial Node @endif
                </span>
            </div>

            <div class="mt-2 text-sm font-black text-white line-clamp-2">
                {{ $shipment->current_location_description }}
            </div>

            @if($latestLocationEv && $latestLocationEv->hasCoordinates())
                <div class="mt-1 font-mono text-[11px] text-cyan-400">
                    Coords: {{ number_format($latestLocationEv->latitude, 4) }}, {{ number_format($latestLocationEv->longitude, 4) }}
                </div>
            @endif

            <div class="mt-2 text-[10px] text-slate-400 flex items-center space-x-1">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>
                    @if($latestLocationEv)
                        Pinged {{ $latestLocationEv->event_time->diffForHumans() }} ({{ $latestLocationEv->event_time->format('M d, H:i') }})
                    @else
                        Awaiting initial dispatch telemetry
                    @endif
                </span>
            </div>
        </div>

        <!-- Origin -->
        <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800">
            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Origin Node</div>
            <div class="mt-1 text-sm font-bold text-white">{{ $shipment->originLocation->name }}</div>
            <div class="text-xs text-emerald-400 font-semibold">{{ $shipment->originLocation->city }}, {{ $shipment->originLocation->country }} ({{ $shipment->originLocation->code }})</div>
            <div class="mt-2 text-[11px] text-slate-400">Departed: {{ $shipment->expected_departure->format('M d, Y - H:i') }}</div>
        </div>

        <!-- Destination -->
        <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800">
            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Destination Node</div>
            <div class="mt-1 text-sm font-bold text-white">{{ $shipment->destinationLocation->name }}</div>
            <div class="text-xs text-blue-400 font-semibold">{{ $shipment->destinationLocation->city }}, {{ $shipment->destinationLocation->country }} ({{ $shipment->destinationLocation->code }})</div>
            <div class="mt-2 text-[11px] text-slate-400">Target ETA: {{ $shipment->estimated_arrival ? $shipment->estimated_arrival->format('M d, Y - H:i') : $shipment->expected_arrival->format('M d, Y - H:i') }}</div>
        </div>

        <!-- Cargo & Specs -->
        <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800">
            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Cargo & Specs</div>
            <div class="mt-1 text-xs font-bold text-slate-200 truncate">{{ $shipment->cargo_type }}</div>
            <div class="mt-1 flex items-center space-x-3 text-xs text-slate-400">
                <span>Weight: <strong class="text-white">{{ number_format($shipment->weight, 0) }} kg</strong></span>
                <span>Vol: <strong class="text-white">{{ number_format($shipment->volume, 1) }} m³</strong></span>
            </div>
            <div class="mt-2 text-[11px] text-slate-400">{{ $shipment->items->count() }} Manifest Items</div>
        </div>
    </div>

    <!-- Interactive Route Map (Leaflet.js) -->
    <div class="bg-slate-900/90 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-white flex items-center space-x-2">
                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    <span>Geospatial Maritime & Air Corridor Map</span>
                </h2>
                <p class="text-[11px] text-slate-400">Live plotting of departure, historical checkpoints and destination terminal</p>
            </div>
            <div class="flex items-center space-x-4 text-xs">
                <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span><span class="text-slate-300">Origin</span></span>
                <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-cyan-400"></span><span class="text-slate-300">Waypoints</span></span>
                <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span><span class="text-slate-300">Exception Spot</span></span>
                <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span><span class="text-slate-300">Destination</span></span>
            </div>
        </div>

        <div id="shipmentMap" class="h-96 w-full bg-slate-950"></div>
    </div>

    <!-- Active Exceptions Section (if any exist) -->
    @if($shipment->exceptions->isNotEmpty())
        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 shadow-xl p-5">
            <h3 class="text-sm font-bold text-white flex items-center space-x-2 mb-4">
                <svg class="w-4 h-4 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>Reported Shipment Incidents & Exceptions</span>
            </h3>

            <div class="space-y-3">
                @foreach($shipment->exceptions as $exc)
                    <div class="p-4 rounded-xl {{ $exc->status === 'resolved' ? 'bg-slate-950/70 border-slate-800' : 'bg-rose-950/30 border-rose-900/60' }} border transition">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold
                                    @if($exc->severity === 'critical') bg-rose-900 text-rose-200 border border-rose-700
                                    @elseif($exc->severity === 'high') bg-amber-900 text-amber-200 border border-amber-700
                                    @else bg-blue-900 text-blue-200 border border-blue-700 @endif">
                                    {{ $exc->severity }} Severity
                                </span>
                                <span class="text-xs font-bold text-white capitalize">{{ str_replace('_', ' ', $exc->type) }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded font-semibold {{ $exc->status === 'resolved' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-amber-950 text-amber-400 border border-amber-800' }}">
                                    {{ $exc->status }}
                                </span>
                            </div>

                            <div class="text-[11px] text-slate-400">
                                Detected: {{ $exc->detected_at->format('M d, Y H:i') }}
                                @if($exc->resolved_at) &bull; Resolved: {{ $exc->resolved_at->format('M d, Y H:i') }} @endif
                            </div>
                        </div>

                        <p class="mt-2 text-xs text-slate-300 whitespace-pre-line">{{ $exc->description }}</p>

                        @if($exc->status !== 'resolved' && (auth()->user()->isAdmin() || auth()->user()->isOperator()))
                            <div class="mt-3 pt-3 border-t border-slate-800/80 flex items-center justify-end">
                                <form action="{{ route('exceptions.resolve', $exc) }}" method="POST" class="flex items-center space-x-2">
                                    @csrf
                                    <input type="text" name="resolution_notes" placeholder="Resolution notes (e.g. Cleared by Port Authority)..." required
                                        class="px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-cyan-500 w-64">
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow transition">
                                        Mark Resolved
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Two Columns: Manifest Items & Chronological Historical Timeline -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Manifest Items Table (1 col) -->
        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 shadow-xl p-5">
            <h3 class="text-sm font-bold text-white mb-3 flex items-center justify-between">
                <span>Cargo Items Manifest</span>
                <span class="text-xs text-cyan-400 font-mono">{{ $shipment->items->count() }} items</span>
            </h3>
            <div class="divide-y divide-slate-800">
                @forelse($shipment->items as $item)
                    <div class="py-3 first:pt-0 last:pb-0">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-200">{{ $item->item_name }}</span>
                            <span class="text-cyan-400 font-mono font-bold">{{ $item->quantity }} pcs</span>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Weight: {{ number_format($item->weight, 0) }} kg</div>
                        @if($item->description)
                            <p class="text-[10px] text-slate-500 mt-1 italic">{{ $item->description }}</p>
                        @endif
                    </div>
                @empty
                    <div class="text-xs text-slate-500 py-4 text-center">No cargo items specified in manifest.</div>
                @endforelse
            </div>
        </div>

        <!-- Historical Chronological Timeline (2 cols) -->
        <div class="lg:col-span-2 bg-slate-900/90 rounded-2xl border border-slate-800 shadow-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-white">Historical Tracking Timeline</h3>
                    <p class="text-[11px] text-slate-400">Append-only immutable record of vessel milestones & telemetry</p>
                </div>
                <span class="text-xs text-slate-400 font-mono bg-slate-800 px-2.5 py-1 rounded-lg border border-slate-700">
                    {{ $timeline->count() }} Historical Events
                </span>
            </div>

            <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-800">
                @forelse($timeline as $event)
                    @php
                        $isExc = $event->isExceptionEvent();
                    @endphp
                    <div class="relative group">
                        <!-- Bullet Icon (Green/Cyan for Normal, Pulsing Amber/Red for Exception) -->
                        <div class="absolute -left-6 top-1 w-4 h-4 rounded-full border-2 border-slate-900 flex items-center justify-center
                            @if($isExc) bg-rose-500 ring-4 ring-rose-500/20
                            @elseif($event->status === 'delivered') bg-emerald-500 ring-2 ring-emerald-500/30
                            @else bg-cyan-400 @endif">
                        </div>

                        <!-- Card Box: Distinct border & background for Exception vs Normal -->
                        <div class="p-4 rounded-xl border transition
                            @if($isExc) bg-rose-950/20 border-rose-800/80 shadow-md shadow-rose-950/30
                            @else bg-slate-950 border-slate-850 hover:border-slate-700 @endif">

                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-bold uppercase tracking-wider
                                        @if($isExc) text-rose-300
                                        @elseif($event->status === 'delivered') text-emerald-400
                                        @else text-white @endif">
                                        {{ str_replace('_', ' ', $event->status) }}
                                    </span>

                                    @if($isExc)
                                        <span class="text-[10px] px-2 py-0.5 rounded bg-rose-900 text-rose-200 font-bold uppercase tracking-wider border border-rose-700">
                                            Operational Exception
                                        </span>
                                    @else
                                        <span class="text-[10px] px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-mono font-medium">
                                            {{ $event->event_type }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] font-mono text-cyan-400 font-semibold">
                                    {{ $event->event_time->format('M d, Y &bull; H:i') }}
                                </span>
                            </div>

                            <p class="text-xs mt-1.5
                                @if($isExc) text-rose-200 font-medium
                                @else text-slate-300 @endif">
                                {{ $event->description }}
                            </p>

                            <div class="mt-2.5 pt-2 border-t border-slate-800/80 flex flex-wrap items-center gap-3 text-[10px] text-slate-400">
                                @if($event->location)
                                    <span class="flex items-center space-x-1 text-slate-300">
                                        <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span class="font-semibold">{{ $event->location->name }} ({{ $event->location->code }})</span>
                                    </span>
                                @endif
                                @if($event->latitude && $event->longitude)
                                    <span class="font-mono text-cyan-400">Coords: {{ number_format($event->latitude, 4) }}, {{ number_format($event->longitude, 4) }}</span>
                                @endif
                                @if($event->creator)
                                    <span>Logged by: <strong class="text-slate-300">{{ $event->creator->name }}</strong></span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-xs text-slate-500 py-6 text-center">No telemetry checkpoints recorded yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Tracking Event (for Admin & Operator) -->
@if(auth()->user()->isAdmin() || auth()->user()->isOperator())
<div id="eventModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <div>
                <h3 class="text-base font-bold text-white">Add Historical Tracking Checkpoint</h3>
                <p class="text-[11px] text-slate-400">Current Status: <strong class="text-cyan-400 uppercase font-mono">{{ $shipment->current_status }}</strong></p>
            </div>
            <button type="button" onclick="document.getElementById('eventModal').classList.add('hidden')" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>

        <form action="{{ route('tracking-events.store', $shipment) }}" method="POST" class="mt-4 space-y-4">
            @csrf

            <!-- Status Transition Selector -->
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">
                    Lifecycle Status Transition *
                </label>
                <select name="status" id="eventStatus" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    <!-- Option to keep current status -->
                    <option value="{{ $shipment->current_status }}" selected>
                        Maintain Current Status: {{ strtoupper(str_replace('_', ' ', $shipment->current_status)) }} (Waypoint Checkpoint)
                    </option>

                    <!-- Allowed next progression statuses -->
                    @if($shipment->current_status === 'created')
                        <option value="booked">&rarr; Transition to: BOOKED</option>
                        <option value="cancelled">&rarr; Transition to: CANCELLED</option>
                    @elseif($shipment->current_status === 'booked')
                        <option value="picked_up">&rarr; Transition to: PICKED_UP (Origin Dispatch)</option>
                        <option value="cancelled">&rarr; Transition to: CANCELLED</option>
                    @elseif($shipment->current_status === 'picked_up')
                        <option value="in_transit">&rarr; Transition to: IN_TRANSIT (Vessel Underway)</option>
                        <option value="cancelled">&rarr; Transition to: CANCELLED</option>
                    @elseif($shipment->current_status === 'in_transit')
                        <option value="delivered">&rarr; Transition to: DELIVERED (Terminal Completion)</option>
                        <option value="exception">&rarr; Transition to: EXCEPTION (Operational Incident)</option>
                        <option value="cancelled">&rarr; Transition to: CANCELLED</option>
                    @elseif($shipment->current_status === 'exception')
                        <option value="in_transit">&rarr; Recover to: IN_TRANSIT (Resumed)</option>
                        <option value="delivered">&rarr; Transition to: DELIVERED</option>
                        <option value="cancelled">&rarr; Transition to: CANCELLED</option>
                    @elseif($shipment->current_status === 'delivered')
                        <option value="in_transit" class="text-rose-400">[Admin Reversal Procedure Required]</option>
                    @endif
                </select>
                <p class="text-[10px] text-slate-500 mt-1">Lifecycle Rule: CREATED &rarr; BOOKED &rarr; PICKED_UP &rarr; IN_TRANSIT &rarr; DELIVERED</p>
            </div>

            <!-- Event Type & Timestamp -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Event Type *</label>
                    <select name="event_type" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="checkpoint">Waypoint Checkpoint</option>
                        <option value="departure">Vessel Departure</option>
                        <option value="arrival">Berthing / Port Arrival</option>
                        <option value="picked_up">Picked Up by Carrier</option>
                        <option value="telemetry_ping">AIS Telemetry Ping</option>
                        <option value="customs_inspection">Customs Inspection</option>
                        <option value="delivery_completed">Final Delivery Signed</option>
                        <option value="weather_alert">Weather Alert</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Timestamp *</label>
                    <input type="datetime-local" name="event_time" id="eventTimeInput" value="{{ now()->format('Y-m-d\TH:i') }}" required
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    @php $lastEv = $timeline->last(); @endphp
                    @if($lastEv)
                        <p class="text-[9px] text-slate-500 mt-0.5">Min chronological: {{ $lastEv->event_time->format('M d, H:i') }}</p>
                    @endif
                </div>
            </div>

            <!-- Location Selection & Coordinates -->
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Port / Hub Location Node</label>
                <select name="location_id" id="eventLocationSelect" onchange="autoFillCoords(this)"
                    class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    <option value="" data-lat="" data-lng="">(Open Sea / Maritime Corridor - Custom Coords)</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" data-lat="{{ $loc->latitude }}" data-lng="{{ $loc->longitude }}">
                            {{ $loc->name }} ({{ $loc->code }}) — {{ $loc->city }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Latitude</label>
                    <input type="number" step="0.000001" name="latitude" id="eventLatitude" placeholder="e.g. 1.290270"
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Longitude</label>
                    <input type="number" step="0.000001" name="longitude" id="eventLongitude" placeholder="e.g. 103.851959"
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Operational Description / Notes *</label>
                <textarea name="description" rows="3" required placeholder="Enter waypoint details, telemetry or operational notes..."
                    class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none"></textarea>
            </div>

            <!-- Special Reversal Section (Only for Terminal DELIVERED status) -->
            @if($shipment->current_status === 'delivered')
                <div class="p-3 rounded-xl bg-rose-950/50 border border-rose-800 space-y-2">
                    <div class="text-[11px] font-bold text-rose-300">Terminal Status Administrative Override</div>
                    <p class="text-[10px] text-slate-400">Reverting a DELIVERED shipment requires an Administrator role and an audited explanation.</p>
                    <label class="flex items-center space-x-2 text-xs text-slate-300">
                        <input type="checkbox" name="allow_terminal_reversal" value="1" class="rounded bg-slate-800 border-slate-700 text-rose-600">
                        <span>Authorize Terminal Status Reversal</span>
                    </label>
                    <input type="text" name="reversal_reason" placeholder="Mandatory reversal justification reason..."
                        class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-xs text-white focus:outline-none">
                </div>
            @endif

            <div class="pt-3 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('eventModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold shadow">
                    Append Historical Checkpoint
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Report Exception (for Admin & Operator) -->
<div id="exceptionModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="text-base font-bold text-white">Log Shipment Exception</h3>
            <button type="button" onclick="document.getElementById('exceptionModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('exceptions.store', $shipment) }}" method="POST" class="mt-4 space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Exception Type</label>
                    <select name="type" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="weather_delay">Weather Delay (Storm / Cyclone)</option>
                        <option value="customs_hold">Customs Hold / Inspection</option>
                        <option value="damaged">Cargo Damaged</option>
                        <option value="route_deviation">Course / Route Deviation</option>
                        <option value="mechanical_issue">Vessel / Carrier Mechanical Issue</option>
                        <option value="documentation_error">Documentation Discrepancy</option>
                        <option value="other">Other Operational Exception</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Severity</label>
                    <select name="severity" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Detected Time</label>
                <input type="datetime-local" name="detected_at" value="{{ now()->format('Y-m-d\TH:i') }}" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Detailed Description</label>
                <textarea name="description" rows="3" required placeholder="Describe the incident, impact on ETA, and immediate actions taken..."
                    class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none"></textarea>
            </div>

            <div class="pt-3 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('exceptionModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow">Submit Exception</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
    function autoFillCoords(selectElem) {
        const selected = selectElem.options[selectElem.selectedIndex];
        const lat = selected.getAttribute('data-lat');
        const lng = selected.getAttribute('data-lng');

        if (lat) document.getElementById('eventLatitude').value = lat;
        if (lng) document.getElementById('eventLongitude').value = lng;
    }

    document.addEventListener("DOMContentLoaded", function () {
        const points = [];

        // Origin
        @if($shipment->originLocation->latitude && $shipment->originLocation->longitude)
            points.push({
                lat: {{ $shipment->originLocation->latitude }},
                lng: {{ $shipment->originLocation->longitude }},
                title: "Origin: {{ $shipment->originLocation->name }} ({{ $shipment->originLocation->code }})",
                type: 'origin'
            });
        @endif

        // Historical Checkpoints
        @foreach($timeline as $ev)
            @if($ev->latitude && $ev->longitude)
                points.push({
                    lat: {{ $ev->latitude }},
                    lng: {{ $ev->longitude }},
                    title: "{{ addslashes($ev->status) }}: {{ addslashes($ev->description) }}",
                    date: "{{ $ev->event_time->format('M d, H:i') }}",
                    type: "{{ $ev->isExceptionEvent() ? 'exception' : 'checkpoint' }}"
                });
            @endif
        @endforeach

        // Destination
        @if($shipment->destinationLocation->latitude && $shipment->destinationLocation->longitude)
            points.push({
                lat: {{ $shipment->destinationLocation->latitude }},
                lng: {{ $shipment->destinationLocation->longitude }},
                title: "Destination: {{ $shipment->destinationLocation->name }} ({{ $shipment->destinationLocation->code }})",
                type: 'destination'
            });
        @endif

        const defaultCenter = points.length > 0 ? [points[0].lat, points[0].lng] : [20.0, 0.0];
        const map = L.map('shipmentMap', {
            zoomControl: true,
            scrollWheelZoom: false
        }).setView(defaultCenter, 3);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);

        const latlngs = [];

        points.forEach((pt) => {
            const latlng = [pt.lat, pt.lng];
            latlngs.push(latlng);

            let color = '#38bdf8';
            let radius = 6;
            if (pt.type === 'origin') {
                color = '#10b981';
                radius = 8;
            } else if (pt.type === 'destination') {
                color = '#3b82f6';
                radius = 8;
            } else if (pt.type === 'exception') {
                color = '#f43f5e';
                radius = 8;
            }

            const marker = L.circleMarker(latlng, {
                color: color,
                fillColor: color,
                fillOpacity: 0.9,
                radius: radius,
                weight: 2
            }).addTo(map);

            marker.bindPopup(`<div class="text-xs">
                <strong style="color: ${color}">${pt.title}</strong>
                ${pt.date ? `<div class="text-[10px] text-slate-400 mt-1">${pt.date}</div>` : ''}
            </div>`);
        });

        if (latlngs.length > 1) {
            const polyline = L.polyline(latlngs, {
                color: '#06b6d4',
                weight: 3,
                opacity: 0.8,
                dashArray: '5, 8',
                lineJoin: 'round'
            }).addTo(map);

            map.fitBounds(polyline.getBounds(), { padding: [50, 50] });
        }
    });
</script>
@endpush
@endsection
