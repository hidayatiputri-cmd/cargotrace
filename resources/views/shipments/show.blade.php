@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <a href="{{ route('shipments.index') }}" class="p-2 rounded-xl bg-[#33215c] hover:bg-[#3d276e] text-slate-300 transition" title="{{ __('messages.show.back_to_registry') }}">
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
                        @else bg-pink-950 text-pink-300 border border-pink-800 @endif">
                        {{ str_replace('_', ' ', $shipment->current_status) }}
                    </span>
                    @if(auth()->user()->isCustomer())
                        <span class="text-[10px] px-2 py-0.5 rounded bg-pink-950 text-pink-300 border border-pink-800 font-semibold">
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
                    class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-pink-600 hover:bg-pink-500 text-white font-bold text-xs shadow-lg shadow-pink-600/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>{{ __('messages.show.add_checkpoint') }}</span>
                </button>

                <button type="button" onclick="document.getElementById('exceptionModal').classList.remove('hidden')"
                    class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>{{ __('messages.show.report_exception') }}</span>
                </button>

                <a href="{{ route('shipments.edit', $shipment) }}" class="p-2 rounded-xl bg-[#33215c] hover:bg-[#3d276e] text-slate-300 border border-[#33215c] transition" title="{{ __('messages.shipments.edit') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
            @endif
        </div>
    </div>

    <!-- Deterministic Delay / SLA Health Banner -->
    @php
        $thresholdHours = (int) config('cargotrace.delay_threshold_hours', 12);
        $excessHours = max(0, $shipment->delay_hours - $thresholdHours);
    @endphp
    @if($shipment->is_delayed)
        <div class="p-4 rounded-2xl bg-gradient-to-r from-rose-950/80 via-[#1c1236] to-rose-950/80 border border-rose-800 shadow-xl flex items-start space-x-3 text-rose-200 text-xs">
            <div class="p-2 rounded-xl bg-rose-500/20 text-rose-400 shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-bold text-rose-300 text-sm">Schedule Exception Detected</span>
                    <span class="px-2.5 py-0.5 rounded-full uppercase font-black text-[10px]
                        @if(strtoupper($shipment->delay_severity) === 'HIGH') bg-rose-900 text-rose-200 border border-rose-600
                        @elseif(strtoupper($shipment->delay_severity) === 'MEDIUM') bg-amber-900 text-amber-200 border border-amber-600
                        @else bg-blue-900 text-blue-200 border border-blue-600 @endif">
                        Severity: {{ strtoupper($shipment->delay_severity) }}
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-[#120b24] text-slate-200 border border-[#33215c]">
                        +{{ $shipment->delay_hours }}h Total Delay
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-950/60 text-rose-300 border border-rose-800">
                        +{{ $excessHours }}h beyond {{ $thresholdHours }}h SLA Threshold
                    </span>
                </div>

                <!-- Deterministic Calculation Reason -->
                <p class="mt-2 text-slate-200 font-medium">
                    {{ $shipment->delay_reason ?? "Total delay exceeds configured SLA threshold ({$thresholdHours}h) by {$excessHours}h." }}
                </p>

                <div class="mt-2 text-[11px] text-slate-400 flex flex-wrap items-center gap-4">
                    <span>Expected Arrival: <strong class="text-white">{{ $shipment->expected_arrival->format('M d, Y H:i') }}</strong></span>
                    <span>Revised ETA: <strong class="text-rose-400">{{ $shipment->estimated_arrival?->format('M d, Y H:i') ?? 'N/A' }}</strong></span>
                    <span>SLA Threshold: <strong class="text-white">{{ $thresholdHours }} Hours</strong></span>
                </div>
            </div>
        </div>
    @else
        <div class="p-4 rounded-2xl bg-[#1c1236] border border-emerald-900/60 shadow-xl flex items-center justify-between text-xs">
            <div class="flex items-center space-x-3">
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-bold text-white text-sm">Schedule Health: On Schedule</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-950 text-emerald-400 border border-emerald-800">
                            SLA COMPLIANT
                        </span>
                        <span class="text-[10px] text-slate-400 font-mono">
                            Threshold: {{ $thresholdHours }}h
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        {{ $shipment->delay_reason ?? "Estimated arrival is within contractual SLA tolerance (Buffer: {$thresholdHours} hours)." }}
                    </p>
                </div>
            </div>
            <div class="text-right hidden sm:block">
                <div class="text-[11px] text-slate-400">Target ETA:</div>
                <div class="font-bold text-emerald-400 text-xs">{{ $shipment->estimated_arrival ? $shipment->estimated_arrival->format('M d, Y H:i') : $shipment->expected_arrival->format('M d, Y H:i') }}</div>
            </div>
        </div>
    @endif

    <!-- Lifecycle Progress Bar: CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> DELIVERED -->
    @php
        $statusOrder = ['created', 'booked', 'picked_up', 'in_transit', 'delivered'];
        $currentIdx = array_search(strtolower($shipment->current_status), $statusOrder, true);
        if ($currentIdx === false) {
            $currentIdx = 3;
        }
    @endphp
    <div class="bg-[#1c1236] p-5 rounded-2xl border border-[#33215c] shadow-xl">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Shipment Lifecycle Progression</span>
            <span class="text-[11px] font-mono text-pink-400">Stage {{ $currentIdx + 1 }} of 5</span>
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
                                @else bg-pink-500 text-white ring-4 ring-pink-500/30 animate-pulse @endif
                            @else bg-[#120b24] text-slate-500 border border-[#33215c] @endif">
                            @if($isPassed)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            @else
                                {{ $idx + 1 }}
                            @endif
                        </div>
                    </div>
                    <div class="mt-2 text-[10px] font-bold tracking-wider uppercase
                        @if($isCurrent) text-pink-400
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
        <!-- Current Location Card -->
        <div class="bg-gradient-to-br from-[#1c1236] to-[#120b24] p-4 rounded-2xl border border-pink-800/60 shadow-xl relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold text-pink-400 tracking-wider flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-pink-400 animate-ping"></span>
                    <span>Current Live Location</span>
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded bg-pink-950 text-pink-300 border border-pink-800 font-semibold">
                    @if($latestLocationEv?->location) Terminal Hub @elseif($latestLocationEv?->hasCoordinates()) GPS Telemetry @else Initial Node @endif
                </span>
            </div>

            <div class="mt-2 text-sm font-black text-white line-clamp-2">
                {{ $shipment->current_location_description }}
            </div>

            @if($latestLocationEv && $latestLocationEv->hasCoordinates())
                <div class="mt-1 font-mono text-[11px] text-pink-400">
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
        <div class="bg-[#1c1236] p-4 rounded-2xl border border-[#33215c]">
            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Origin Node</div>
            <div class="mt-1 text-sm font-bold text-white">{{ $shipment->originLocation->name }}</div>
            <div class="text-xs text-emerald-400 font-semibold">{{ $shipment->originLocation->city }}, {{ $shipment->originLocation->country }} ({{ $shipment->originLocation->code }})</div>
            <div class="mt-2 text-[11px] text-slate-400">Departed: {{ $shipment->expected_departure->format('M d, Y - H:i') }}</div>
        </div>

        <!-- Destination -->
        <div class="bg-[#1c1236] p-4 rounded-2xl border border-[#33215c]">
            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Destination Node</div>
            <div class="mt-1 text-sm font-bold text-white">{{ $shipment->destinationLocation->name }}</div>
            <div class="text-xs text-blue-400 font-semibold">{{ $shipment->destinationLocation->city }}, {{ $shipment->destinationLocation->country }} ({{ $shipment->destinationLocation->code }})</div>
            <div class="mt-2 text-[11px] text-slate-400">Target ETA: {{ $shipment->estimated_arrival ? $shipment->estimated_arrival->format('M d, Y - H:i') : $shipment->expected_arrival->format('M d, Y - H:i') }}</div>
        </div>

        <!-- Cargo & Specs -->
        <div class="bg-[#1c1236] p-4 rounded-2xl border border-[#33215c]">
            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Cargo & Specs</div>
            <div class="mt-1 text-xs font-bold text-slate-200 truncate">{{ $shipment->cargo_type }}</div>
            <div class="mt-1 flex items-center space-x-3 text-xs text-slate-400">
                <span>Weight: <strong class="text-white">{{ number_format($shipment->weight, 0) }} kg</strong></span>
                <span>Vol: <strong class="text-white">{{ number_format($shipment->volume, 1) }} m³</strong></span>
            </div>
            <div class="mt-2 text-[11px] text-slate-400">{{ $shipment->items->count() }} Manifest Items</div>
        </div>
    </div>

    <!-- External Weather & Environmental Operational Context (Open-Meteo / OpenWeatherMap) -->
    @if(isset($weather) && $weather['available'])
        <div class="bg-gradient-to-r from-[#1c1236] via-[#1c1236]/95 to-fuchsia-950/25 p-5 rounded-2xl border border-[#33215c] shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-[#33215c]">
                <div class="flex items-center space-x-3">
                    <div class="p-2.5 rounded-xl bg-pink-500/10 text-pink-400 border border-pink-500/20 shrink-0">
                        @if($weather['condition_icon'] === 'sun')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        @elseif($weather['condition_icon'] === 'cloud-rain')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15zm5 5v2m4-2v2m4-2v2"/></svg>
                        @elseif($weather['condition_icon'] === 'cloud-lightning')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        @else
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                        @endif
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm font-bold text-white">Live Environmental & Maritime Weather Telemetry</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $weather['advisory_badge'] }}">
                                {{ $weather['advisory_level'] }}
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-[#120b24] text-slate-300 font-mono border border-[#33215c]">
                                {{ $weather['source'] }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Observation Target: <strong class="text-slate-200">{{ $weather['location_label'] ?? 'Current Telemetry Position' }}</strong>
                            &bull; Coords: <span class="font-mono text-pink-400">{{ number_format($weather['latitude'], 4) }}, {{ number_format($weather['longitude'], 4) }}</span>
                        </p>
                    </div>
                </div>

                <div class="text-[11px] text-slate-400 flex items-center space-x-1.5 shrink-0">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Updated: <strong class="text-slate-300">{{ $weather['fetched_at'] }}</strong></span>
                </div>
            </div>

            <!-- Metrics Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                <div class="bg-[#120b24] p-3 rounded-xl border border-[#33215c]">
                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Atmospheric Condition</span>
                    <div class="mt-1 text-sm font-bold text-white flex items-center space-x-1.5 truncate">
                        <span>{{ $weather['condition_name'] }}</span>
                    </div>
                </div>

                <div class="bg-[#120b24] p-3 rounded-xl border border-[#33215c]">
                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Ambient Temperature</span>
                    <div class="mt-1 text-sm font-bold text-pink-400 font-mono">
                        {{ $weather['temperature'] !== null ? $weather['temperature'] . ' ' . $weather['temperature_unit'] : 'N/A' }}
                    </div>
                </div>

                <div class="bg-[#120b24] p-3 rounded-xl border border-[#33215c]">
                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Surface Wind Velocity</span>
                    <div class="mt-1 text-sm font-bold text-white font-mono">
                        {{ $weather['wind_speed'] }} {{ $weather['wind_unit'] }}
                        @if(!empty($weather['wind_direction']))
                            <span class="text-[11px] text-slate-400 font-sans font-normal">({{ $weather['wind_direction'] }}°)</span>
                        @endif
                    </div>
                </div>

                <div class="bg-[#120b24] p-3 rounded-xl border border-[#33215c]">
                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Relative Humidity</span>
                    <div class="mt-1 text-sm font-bold text-white font-mono">
                        {{ $weather['humidity'] !== null ? $weather['humidity'] . '%' : 'N/A' }}
                    </div>
                </div>
            </div>

            <!-- Deterministic Operational Advisory & Non-AI Notice -->
            <div class="mt-4 p-3 rounded-xl bg-[#120b24] border border-[#33215c] flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-start space-x-2.5">
                    <svg class="w-4 h-4 text-pink-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <p class="text-slate-200 font-medium">{{ $weather['advisory_text'] }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $weather['operational_note'] }}</p>
                    </div>
                </div>
                <div class="shrink-0 text-right">
                    <span class="text-[10px] font-semibold text-slate-400 px-2 py-0.5 rounded bg-[#1c1236] border border-[#33215c]">
                        Operational Advisory
                    </span>
                </div>
            </div>
        </div>
    @elseif(isset($weather))
        <!-- Graceful Fallback Banner When Weather Telemetry is Unavailable or Offline -->
        <div class="p-4 rounded-2xl bg-[#1c1236] border border-[#33215c] shadow-xl flex items-start space-x-3 text-xs">
            <div class="p-2 rounded-xl bg-amber-500/10 text-amber-400 shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="flex-1">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-slate-300">Operational Weather Telemetry</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-[#120b24] text-slate-400">Offline / Standby</span>
                </div>
                <p class="text-slate-400 text-[11px] mt-1">{{ $weather['message'] ?? 'Data telemetri cuaca eksternal sementara tidak dapat dimuat.' }}</p>
                <p class="text-slate-500 text-[10px] mt-0.5">{{ $weather['operational_note'] ?? 'Sistem pelacakan historis dan perhitungan jadwal SLA tetap berjalan aman.' }}</p>
            </div>
        </div>
    @endif

    <!-- Interactive Route Map (Leaflet.js + OpenStreetMap) -->
    <div class="bg-[#1c1236] rounded-2xl border border-[#33215c] shadow-xl overflow-hidden">
        <div class="p-4 border-b border-[#33215c] flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-white flex items-center space-x-2">
                    <svg class="w-4 h-4 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    <span>Geospatial Maritime & Air Corridor Map</span>
                </h2>
                <p class="text-[11px] text-slate-400">
                    Live plotting of departure, historical checkpoints, and destination terminal via
                    <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener" class="text-pink-400 underline hover:text-pink-300">OpenStreetMap</a>
                </p>
            </div>

            <!-- Legend & Interactive Controls -->
            <div class="flex flex-wrap items-center gap-3 text-xs">
                <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span><span class="text-slate-300 text-[11px]">Origin</span></span>
                <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span><span class="text-slate-300 text-[11px]">Waypoints</span></span>
                <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span><span class="text-slate-300 text-[11px]">Incident Spot</span></span>
                <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span><span class="text-slate-300 text-[11px]">Destination</span></span>
                <button type="button" id="btnResetMapView" class="px-2.5 py-1 rounded-lg bg-[#33215c] hover:bg-[#3d276e] text-slate-300 text-[11px] font-semibold border border-[#33215c] transition">
                    Fit Bounds
                </button>
            </div>
        </div>

        <div id="shipmentMap" class="h-[420px] w-full bg-[#120b24] relative z-0"></div>
    </div>

    <!-- Operator Exception Management Section -->
    <div class="bg-[#1c1236] rounded-2xl border border-[#33215c] shadow-xl p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#33215c]">
            <div>
                <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                    <svg class="w-4 h-4 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span>Operational Exception Management</span>
                </h3>
                <p class="text-[11px] text-slate-400">Lifecycle: <strong class="text-amber-400">OPEN</strong> &rarr; <strong class="text-purple-400">INVESTIGATING</strong> &rarr; <strong class="text-emerald-400">RESOLVED</strong></p>
            </div>

            @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
                <button type="button" onclick="document.getElementById('exceptionModal').classList.remove('hidden')"
                    class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Report Exception</span>
                </button>
            @endif
        </div>

        <div class="mt-4 space-y-4">
            @forelse($shipment->exceptions as $exc)
                @php
                    $excStatus = strtoupper(trim($exc->status));
                    $excSeverity = strtoupper(trim($exc->severity));
                @endphp
                <div class="p-4 rounded-xl border transition
                    @if($excStatus === 'RESOLVED') bg-[#120b24] border-[#33215c]
                    @elseif($excStatus === 'INVESTIGATING') bg-purple-950/20 border-purple-800/70
                    @else bg-rose-950/25 border-rose-900/70 @endif">

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Type Badge -->
                            <span class="px-2.5 py-0.5 rounded text-xs font-mono font-bold text-white bg-[#33215c] border border-[#33215c]">
                                {{ $exc->type }}
                            </span>

                            <!-- Severity Badge -->
                            <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold
                                @if($excSeverity === 'CRITICAL' || $excSeverity === 'HIGH') bg-rose-900 text-rose-200 border border-rose-700
                                @elseif($excSeverity === 'MEDIUM') bg-amber-900 text-amber-200 border border-amber-700
                                @else bg-blue-900 text-blue-200 border border-blue-700 @endif">
                                {{ $excSeverity }} Severity
                            </span>

                            <!-- Status Workflow Badge -->
                            <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                @if($excStatus === 'RESOLVED') bg-emerald-950 text-emerald-400 border border-emerald-800
                                @elseif($excStatus === 'INVESTIGATING') bg-purple-950 text-purple-300 border border-purple-800 animate-pulse
                                @else bg-rose-950 text-rose-300 border border-rose-800 @endif">
                                <span>{{ $excStatus }}</span>
                            </span>
                        </div>

                        <div class="text-[11px] text-slate-400">
                            Detected: <strong class="text-slate-300">{{ $exc->detected_at->format('M d, Y H:i') }}</strong>
                            @if($exc->resolved_at) &bull; Cleared: <strong class="text-emerald-400">{{ $exc->resolved_at->format('M d, Y H:i') }}</strong> @endif
                        </div>
                    </div>

                    <p class="mt-2.5 text-xs text-slate-200 whitespace-pre-line leading-relaxed font-sans">{{ $exc->description }}</p>

                    <!-- Operator Action Controls: OPEN -> INVESTIGATING -> RESOLVED -->
                    @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
                        <div class="mt-3.5 pt-3 border-t border-[#33215c] flex flex-wrap items-center justify-end gap-2">
                            @if($excStatus === 'OPEN')
                                <!-- Form to Start Investigation -->
                                <form action="{{ route('exceptions.investigate', $exc) }}" method="POST" class="inline-flex items-center space-x-1.5">
                                    @csrf
                                    <input type="text" name="investigation_notes" placeholder="Inquiry notes (e.g. Contacting customs broker)..." required
                                        class="px-2.5 py-1 bg-[#120b24] border border-[#33215c] rounded-lg text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-purple-500 w-56">
                                    <button type="submit" class="px-3 py-1 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow transition">
                                        Start Investigation
                                    </button>
                                </form>

                                <!-- Direct Resolve -->
                                <form action="{{ route('exceptions.resolve', $exc) }}" method="POST" class="inline-flex items-center space-x-1.5">
                                    @csrf
                                    <input type="text" name="resolution_notes" placeholder="Quick resolution notes..." required
                                        class="px-2.5 py-1 bg-[#120b24] border border-[#33215c] rounded-lg text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 w-48">
                                    <button type="submit" class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow transition">
                                        Resolve
                                    </button>
                                </form>
                            @elseif($excStatus === 'INVESTIGATING')
                                <!-- Form to Resolve Investigation -->
                                <form action="{{ route('exceptions.resolve', $exc) }}" method="POST" class="inline-flex items-center space-x-2">
                                    @csrf
                                    <input type="text" name="resolution_notes" placeholder="Final resolution notes (e.g. Export certificate verified)..." required
                                        class="px-3 py-1.5 bg-[#120b24] border border-[#33215c] rounded-lg text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 w-64">
                                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow transition">
                                        Mark Resolved
                                    </button>
                                </form>
                            @else
                                <span class="text-[11px] text-emerald-400 font-semibold flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Incident Resolved & Closed</span>
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-6 text-xs text-slate-500 bg-[#120b24]/60 rounded-xl border border-[#33215c]">
                    No active exceptions or operational holds reported on this consignment.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Two Columns: Manifest Items & Chronological Historical Timeline -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Manifest Items Table (1 col) -->
        <div class="bg-[#1c1236] rounded-2xl border border-[#33215c] shadow-xl p-5">
            <h3 class="text-sm font-bold text-white mb-3 flex items-center justify-between">
                <span>Cargo Items Manifest</span>
                <span class="text-xs text-pink-400 font-mono">{{ $shipment->items->count() }} items</span>
            </h3>
            <div class="divide-y divide-[#33215c]">
                @forelse($shipment->items as $item)
                    <div class="py-3 first:pt-0 last:pb-0">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-200">{{ $item->item_name }}</span>
                            <span class="text-pink-400 font-mono font-bold">{{ $item->quantity }} pcs</span>
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
        <div class="lg:col-span-2 bg-[#1c1236] rounded-2xl border border-[#33215c] shadow-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-white">Historical Tracking Timeline</h3>
                    <p class="text-[11px] text-slate-400">Chronological telemetry, milestones, and exception updates</p>
                </div>
                <span class="text-xs text-slate-400 font-mono bg-[#120b24] px-2.5 py-1 rounded-lg border border-[#33215c]">
                    {{ $timeline->count() }} Historical Events
                </span>
            </div>

            <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-[#33215c]">
                @forelse($timeline as $event)
                    @php
                        $isExc = $event->isExceptionEvent();
                    @endphp
                    <div class="relative group">
                        <!-- Bullet Icon (Pink/Emerald for Normal, Pulsing Amber/Red for Exception) -->
                        <div class="absolute -left-6 top-1 w-4 h-4 rounded-full border-2 border-[#1c1236] flex items-center justify-center
                            @if($isExc) bg-rose-500 ring-4 ring-rose-500/20
                            @elseif($event->status === 'delivered') bg-emerald-500 ring-2 ring-emerald-500/30
                            @else bg-pink-500 @endif">
                        </div>

                        <!-- Card Box: Distinct border & background for Exception vs Normal -->
                        <div class="p-4 rounded-xl border transition
                            @if($isExc) bg-rose-950/20 border-rose-800/80 shadow-md shadow-rose-950/30
                            @else bg-[#120b24] border-[#33215c] hover:border-[#3d276e] @endif">

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
                                        <span class="text-[10px] px-2 py-0.5 rounded bg-[#33215c] text-slate-300 font-mono font-medium">
                                            {{ $event->event_type }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] font-mono text-pink-400 font-semibold">
                                    {{ $event->event_time->format('M d, Y &bull; H:i') }}
                                </span>
                            </div>

                            <p class="text-xs mt-1.5
                                @if($isExc) text-rose-200 font-medium
                                @else text-slate-300 @endif">
                                {{ $event->description }}
                            </p>

                            <div class="mt-2.5 pt-2 border-t border-[#33215c] flex flex-wrap items-center gap-3 text-[10px] text-slate-400">
                                @if($event->location)
                                    <span class="flex items-center space-x-1 text-slate-300">
                                        <svg class="w-3.5 h-3.5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span class="font-semibold">{{ $event->location->name }} ({{ $event->location->code }})</span>
                                    </span>
                                @endif
                                @if($event->latitude && $event->longitude)
                                    <span class="font-mono text-pink-400">Coords: {{ number_format($event->latitude, 4) }}, {{ number_format($event->longitude, 4) }}</span>
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
    <div class="bg-[#1c1236] border border-[#33215c] rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">
        <div class="flex items-center justify-between pb-3 border-b border-[#33215c]">
            <div>
                <h3 class="text-base font-bold text-white">Add Historical Tracking Checkpoint</h3>
                <p class="text-[11px] text-slate-400">Current Status: <strong class="text-pink-400 uppercase font-mono">{{ $shipment->current_status }}</strong></p>
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
                <select name="status" id="eventStatus" required class="w-full px-3 py-2 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                    <option value="{{ $shipment->current_status }}" selected>
                        Maintain Current Status: {{ strtoupper(str_replace('_', ' ', $shipment->current_status)) }} (Waypoint Checkpoint)
                    </option>

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
                    <select name="event_type" required class="w-full px-3 py-2 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
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
                        class="w-full px-3 py-2 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
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
                    class="w-full px-3 py-2 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
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
                        class="w-full px-3 py-2 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Longitude</label>
                    <input type="number" step="0.000001" name="longitude" id="eventLongitude" placeholder="e.g. 103.851959"
                        class="w-full px-3 py-2 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Revised Estimated Arrival (Optional)</label>
                <input type="datetime-local" name="estimated_arrival" value="{{ $shipment->estimated_arrival?->format('Y-m-d\TH:i') }}"
                    class="w-full px-3 py-2 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
            </div>

            <!-- Description -->
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Operational Description / Notes *</label>
                <textarea name="description" rows="3" required placeholder="Enter waypoint details, telemetry or operational notes..."
                    class="w-full px-3 py-2 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none"></textarea>
            </div>

            <!-- Special Reversal Section (Only for Terminal DELIVERED status) -->
            @if($shipment->current_status === 'delivered')
                <div class="p-3 rounded-xl bg-rose-950/50 border border-rose-800 space-y-2">
                    <div class="text-[11px] font-bold text-rose-300">Terminal Status Administrative Override</div>
                    <p class="text-[10px] text-slate-400">Reverting a DELIVERED shipment requires an Administrator role and an audited explanation.</p>
                    <label class="flex items-center space-x-2 text-xs text-slate-300">
                        <input type="checkbox" name="allow_terminal_reversal" value="1" class="rounded bg-[#120b24] border-[#33215c] text-rose-600">
                        <span>Authorize Terminal Status Reversal</span>
                    </label>
                    <input type="text" name="reversal_reason" placeholder="Mandatory reversal justification reason..."
                        class="w-full px-2.5 py-1.5 bg-[#120b24] border border-[#33215c] rounded-lg text-xs text-white focus:outline-none">
                </div>
            @endif

            <div class="pt-3 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('eventModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-[#33215c] hover:bg-[#3d276e] text-slate-300 text-xs font-semibold">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-pink-600 hover:bg-pink-500 text-white text-xs font-bold shadow">
                    Append Historical Checkpoint
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Report Exception (for Admin & Operator) -->
<div id="exceptionModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-[#1c1236] border border-[#33215c] rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">
        <div class="flex items-center justify-between pb-3 border-b border-[#33215c]">
            <div>
                <h3 class="text-base font-bold text-white">Log Operational Exception</h3>
                <p class="text-[11px] text-slate-400">Flag customs holds, weather delays, damage or congestion</p>
            </div>
            <button type="button" onclick="document.getElementById('exceptionModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('exceptions.store', $shipment) }}" method="POST" class="mt-4 space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Exception Type *</label>
                    <select name="type" required class="w-full px-3 py-2 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                        <option value="CUSTOMS_HOLD">CUSTOMS_HOLD — Regulatory Inspection</option>
                        <option value="WEATHER_DELAY">WEATHER_DELAY — Severe Weather / Cyclone</option>
                        <option value="PORT_CONGESTION">PORT_CONGESTION — Berth Queuing</option>
                        <option value="DAMAGED">DAMAGED — Packaging / Cargo Damage</option>
                        <option value="MISROUTED">MISROUTED — Course Deviation</option>
                        <option value="DOCUMENTATION_ISSUE">DOCUMENTATION_ISSUE — HS Code Discrepancy</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Severity *</label>
                    <select name="severity" required class="w-full px-3 py-2 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                        <option value="LOW">LOW</option>
                        <option value="MEDIUM" selected>MEDIUM</option>
                        <option value="HIGH">HIGH</option>
                        <option value="CRITICAL">CRITICAL</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Detected Time *</label>
                    <input type="datetime-local" name="detected_at" value="{{ now()->format('Y-m-d\TH:i') }}" required
                        class="w-full px-3 py-2 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Est. Delay Impact (Hours)</label>
                    <input type="number" name="estimated_delay_hours" min="0" placeholder="e.g. 24"
                        class="w-full px-3 py-2 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Detailed Description *</label>
                <textarea name="description" rows="3" required placeholder="Describe the incident, operational impact, and mitigation actions..."
                    class="w-full px-3 py-2 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none"></textarea>
            </div>

            <div class="pt-3 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('exceptionModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-[#33215c] hover:bg-[#3d276e] text-slate-300 text-xs font-semibold">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow">
                    Submit Exception & Flag Timeline
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
@php
    $originPoint = null;
    if ($shipment->originLocation && $shipment->originLocation->latitude !== null && $shipment->originLocation->longitude !== null) {
        $originPoint = [
            'type' => 'origin',
            'lat' => (float) $shipment->originLocation->latitude,
            'lng' => (float) $shipment->originLocation->longitude,
            'name' => $shipment->originLocation->name,
            'code' => $shipment->originLocation->code,
            'city' => $shipment->originLocation->city,
            'country' => $shipment->originLocation->country,
            'status' => 'ORIGIN DISPATCH NODE',
            'event_type' => 'Facility Origin',
            'time' => $shipment->expected_departure->format('M d, Y H:i'),
            'time_human' => 'Scheduled Departure',
            'description' => 'Origin facility & port of departure: ' . $shipment->originLocation->city . ', ' . $shipment->originLocation->country,
            'is_exception' => false,
            'badge' => 'bg-emerald-950 text-emerald-400 border border-emerald-800',
        ];
    }

    $eventPoints = [];
    $isDelivered = ($shipment->current_status === 'delivered');

    foreach ($timeline as $ev) {
        if ($ev->latitude !== null && $ev->longitude !== null) {
            $isExc = $ev->isExceptionEvent();
            $eventPoints[] = [
                'type' => $isExc ? 'exception' : 'waypoint',
                'lat' => (float) $ev->latitude,
                'lng' => (float) $ev->longitude,
                'name' => $ev->location ? $ev->location->name : 'Transit Corridor GPS Telemetry',
                'code' => $ev->location?->code ?? 'GPS',
                'city' => $ev->location?->city ?? 'Corridor',
                'country' => $ev->location?->country ?? '',
                'status' => strtoupper(str_replace('_', ' ', $ev->status)),
                'event_type' => ucwords(str_replace('_', ' ', $ev->event_type)),
                'time' => $ev->event_time->format('M d, Y H:i'),
                'time_human' => $ev->event_time->diffForHumans(),
                'description' => $ev->description,
                'is_exception' => $isExc,
                'created_by' => $ev->creator?->name ?? 'Operations Desk',
                'badge' => $isExc
                    ? 'bg-rose-950 text-rose-300 border border-rose-800'
                    : 'bg-pink-950 text-pink-300 border border-pink-800',
            ];
        }
    }

    $destinationPoint = null;
    if ($shipment->destinationLocation && $shipment->destinationLocation->latitude !== null && $shipment->destinationLocation->longitude !== null) {
        $destinationPoint = [
            'type' => 'destination',
            'lat' => (float) $shipment->destinationLocation->latitude,
            'lng' => (float) $shipment->destinationLocation->longitude,
            'name' => $shipment->destinationLocation->name,
            'code' => $shipment->destinationLocation->code,
            'city' => $shipment->destinationLocation->city,
            'country' => $shipment->destinationLocation->country,
            'status' => 'DESTINATION DISCHARGE PORT',
            'event_type' => 'Final Destination',
            'time' => $shipment->estimated_arrival ? $shipment->estimated_arrival->format('M d, Y H:i') : $shipment->expected_arrival->format('M d, Y H:i'),
            'time_human' => 'Target Arrival',
            'description' => 'Target discharge terminal & consignee receiving port: ' . $shipment->destinationLocation->city . ', ' . $shipment->destinationLocation->country,
            'is_exception' => false,
            'badge' => 'bg-blue-950 text-blue-400 border border-blue-800',
        ];
    }
@endphp
<script>
    function autoFillCoords(selectElem) {
        const selected = selectElem.options[selectElem.selectedIndex];
        const lat = selected.getAttribute('data-lat');
        const lng = selected.getAttribute('data-lng');

        if (lat) document.getElementById('eventLatitude').value = lat;
        if (lng) document.getElementById('eventLongitude').value = lng;
    }

    document.addEventListener("DOMContentLoaded", function () {
        const originPoint = @json($originPoint);
        const eventPoints = @json($eventPoints);
        const destinationPoint = @json($destinationPoint);
        const isDelivered = {{ $isDelivered ? 'true' : 'false' }};

        // Determine center coordinate
        let defaultCenter = [20.0, 0.0];
        let defaultZoom = 2;

        if (eventPoints.length > 0) {
            const lastEv = eventPoints[eventPoints.length - 1];
            defaultCenter = [lastEv.lat, lastEv.lng];
            defaultZoom = 4;
        } else if (originPoint) {
            defaultCenter = [originPoint.lat, originPoint.lng];
            defaultZoom = 4;
        }

        // Initialize Leaflet Map
        const map = L.map('shipmentMap', {
            zoomControl: true,
            scrollWheelZoom: false,
            minZoom: 2,
            maxZoom: 18
        }).setView(defaultCenter, defaultZoom);

        // Standard OpenStreetMap TileLayer with explicit attribution
        const osmStandard = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
        }).addTo(map);

        // All markers and coordinates for bounds fitting
        const allMarkers = [];
        const allCoords = [];
        const traveledCoords = [];

        // Helper to format popup HTML
        function createPopupContent(pt, colorHex) {
            return `
                <div class="p-2.5 min-w-[240px] max-w-[280px] font-sans">
                    <div class="flex items-center justify-between gap-2 pb-1.5 border-b border-[#33215c]">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded ${pt.badge || 'bg-[#1c1236] text-slate-200'}">
                            ${pt.status}
                        </span>
                        <span class="text-[10px] text-slate-400 font-mono">${pt.event_type || ''}</span>
                    </div>
                    <div class="mt-2 text-xs font-bold text-white leading-tight">${pt.name}</div>
                    <div class="text-[10px] text-pink-400 font-mono mt-0.5">
                        ${pt.city ? `${pt.city}, ` : ''}${pt.country || ''} &bull; [${pt.lat.toFixed(4)}, ${pt.lng.toFixed(4)}]
                    </div>
                    <div class="mt-2 text-[11px] text-slate-300 leading-relaxed bg-[#120b24] p-2 rounded-lg border border-[#33215c]">
                        ${pt.description}
                    </div>
                    <div class="mt-2 flex items-center justify-between text-[10px] text-slate-400">
                        <span>${pt.time}</span>
                        ${pt.created_by ? `<span>By: <strong class="text-slate-300">${pt.created_by}</strong></span>` : ''}
                    </div>
                </div>
            `;
        }

        // 1. Plot Origin Node
        if (originPoint) {
            const originCoord = [originPoint.lat, originPoint.lng];
            allCoords.push(originCoord);
            traveledCoords.push(originCoord);

            const originMarker = L.circleMarker(originCoord, {
                radius: 9,
                color: '#ffffff',
                weight: 2.5,
                fillColor: '#10b981', // Emerald
                fillOpacity: 1.0
            }).addTo(map);

            originMarker.bindPopup(createPopupContent(originPoint, '#10b981'));
            allMarkers.push(originMarker);
        }

        // 2. Plot Tracking Events
        eventPoints.forEach((ev, idx) => {
            const coord = [ev.lat, ev.lng];
            allCoords.push(coord);
            traveledCoords.push(coord);

            const isLatest = (idx === eventPoints.length - 1);
            let color = ev.is_exception ? '#f43f5e' : '#ec4899';
            let radius = isLatest ? 9 : 7;

            // Highlight latest active telemetry ping with ping ring
            if (isLatest) {
                const pulseIcon = L.divIcon({
                    className: 'relative',
                    html: `
                        <div class="relative flex items-center justify-center">
                            <span class="absolute w-6 h-6 rounded-full ${ev.is_exception ? 'bg-rose-500/40' : 'bg-pink-500/40'} animate-ping"></span>
                            <span class="relative block w-4 h-4 rounded-full border-2 border-white ${ev.is_exception ? 'bg-rose-500' : 'bg-pink-500'} shadow-lg"></span>
                        </div>
                    `,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                });
                const pulseMarker = L.marker(coord, { icon: pulseIcon }).addTo(map);
                pulseMarker.bindPopup(createPopupContent(ev, color));
                allMarkers.push(pulseMarker);
            } else {
                const marker = L.circleMarker(coord, {
                    radius: radius,
                    color: '#ffffff',
                    weight: 2,
                    fillColor: color,
                    fillOpacity: 0.95
                }).addTo(map);

                marker.bindPopup(createPopupContent(ev, color));
                allMarkers.push(marker);
            }
        });

        // 3. Plot Destination Node
        if (destinationPoint) {
            const destCoord = [destinationPoint.lat, destinationPoint.lng];
            allCoords.push(destCoord);

            if (isDelivered) {
                traveledCoords.push(destCoord);
            }

            const destMarker = L.circleMarker(destCoord, {
                radius: 9,
                color: '#ffffff',
                weight: 2.5,
                fillColor: '#3b82f6', // Blue
                fillOpacity: 1.0
            }).addTo(map);

            destMarker.bindPopup(createPopupContent(destinationPoint, '#3b82f6'));
            allMarkers.push(destMarker);
        }

        // 4. Polylines
        // (A) Solid line for traveled path
        if (traveledCoords.length > 1) {
            L.polyline(traveledCoords, {
                color: '#ec4899',
                weight: 3.5,
                opacity: 0.9,
                lineJoin: 'round'
            }).addTo(map);
        }

        // (B) Dashed line for remaining path to destination (if not yet delivered)
        if (!isDelivered && destinationPoint && traveledCoords.length > 0) {
            const lastCoord = traveledCoords[traveledCoords.length - 1];
            const destCoord = [destinationPoint.lat, destinationPoint.lng];

            L.polyline([lastCoord, destCoord], {
                color: '#3b82f6',
                weight: 2.5,
                opacity: 0.7,
                dashArray: '6, 8',
                lineJoin: 'round'
            }).addTo(map);
        }

        // Auto-fit bounds
        function fitMapToBounds() {
            if (allCoords.length > 1) {
                const bounds = L.latLngBounds(allCoords);
                map.fitBounds(bounds, { padding: [50, 50], maxZoom: 12 });
            } else if (allCoords.length === 1) {
                map.setView(allCoords[0], 6);
            }
        }

        fitMapToBounds();

        // Fit Bounds Button
        const resetBtn = document.getElementById('btnResetMapView');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                fitMapToBounds();
            });
        }

        // Invalidate map size on window resize
        window.addEventListener('resize', function () {
            map.invalidateSize();
        });
    });
</script>
@endpush
@endsection
