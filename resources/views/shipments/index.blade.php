@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <h1 class="text-2xl font-extrabold text-white">{{ __('messages.shipments.registry_title') }}</h1>
                @if(auth()->user()->isCustomer())
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-pink-950/60 text-pink-300 border border-pink-800">
                        {{ __('messages.shipments.consignee_fleet') }}
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-[#101935] text-slate-300 border border-[#1e294b]">
                        {{ __('messages.shipments.dispatch_grid') }}
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-400 mt-1">
                @if(auth()->user()->isCustomer())
                    {{ __('messages.shipments.subtitle_customer') }}
                @else
                    {{ __('messages.shipments.subtitle_ops') }}
                @endif
            </p>
        </div>

        @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
            <a href="{{ route('shipments.create') }}" class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-pink-500 to-rose-600 hover:from-pink-400 hover:to-rose-500 text-white shadow-lg shadow-pink-500/25 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.shipments.register_new') }}</span>
            </a>
        @endif
    </div>

    <!-- Advanced Filter Console -->
    <div class="bg-[#101935] p-5 rounded-2xl border border-[#1e294b] shadow-xl" x-data="{ expanded: {{ request()->hasAny(['origin_location_id', 'destination_location_id', 'delay_severity', 'has_exceptions', 'departure_from', 'departure_to', 'arrival_from', 'arrival_to']) ? 'true' : 'false' }} }">
        <form method="GET" action="{{ route('shipments.index') }}" class="space-y-4">
            <!-- Primary Filter Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('messages.shipments.search') }}</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.shipments.search_placeholder') }}"
                        class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                </div>

                <!-- Status Filter with full Lifecycle -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('messages.shipments.lifecycle_status') }}</label>
                    <select name="status" class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                        <option value="">{{ __('messages.shipments.all_lifecycle_stages') }}</option>
                        <option value="created" {{ request('status') === 'created' ? 'selected' : '' }}>{{ __('messages.status.created') }}</option>
                        <option value="booked" {{ request('status') === 'booked' ? 'selected' : '' }}>{{ __('messages.status.booked') }}</option>
                        <option value="picked_up" {{ request('status') === 'picked_up' ? 'selected' : '' }}>{{ __('messages.status.picked_up') }}</option>
                        <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>{{ __('messages.status.in_transit') }}</option>
                        <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>{{ __('messages.status.delivered') }}</option>
                        <option value="exception" {{ request('status') === 'exception' ? 'selected' : '' }}>{{ __('messages.status.exception') }}</option>
                        <option value="customs_hold" {{ request('status') === 'customs_hold' ? 'selected' : '' }}>{{ __('messages.status.customs_hold') }}</option>
                    </select>
                </div>

                <!-- Schedule Health / Delay -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('messages.shipments.schedule_health') }}</label>
                    <select name="is_delayed" class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                        <option value="">{{ __('messages.shipments.all_schedules') }}</option>
                        <option value="1" {{ request('is_delayed') === '1' ? 'selected' : '' }}>{{ __('messages.shipments.delayed_only') }}</option>
                        <option value="0" {{ request('is_delayed') === '0' ? 'selected' : '' }}>{{ __('messages.shipments.on_time_only') }}</option>
                    </select>
                </div>

                <!-- Carrier Filter -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('messages.shipments.carrier') }}</label>
                    <select name="carrier_id" class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                        <option value="">{{ __('messages.shipments.all_carriers') }}</option>
                        @foreach($carriers as $car)
                            <option value="{{ $car->id }}" {{ request('carrier_id') == $car->id ? 'selected' : '' }}>
                                {{ $car->name }} ({{ $car->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action & Expand Buttons -->
                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 hover:from-pink-400 hover:to-rose-500 text-white font-bold text-xs shadow-lg shadow-pink-500/20 transition">
                        {{ __('messages.shipments.filter_btn') }}
                    </button>
                    <button type="button" @click="expanded = !expanded" class="py-2 px-3 rounded-xl bg-[#1e294b] hover:bg-[#25335e] text-slate-300 font-semibold text-xs border border-[#1e294b] transition flex items-center space-x-1" title="Toggle Advanced Filter Parameters">
                        <span x-text="expanded ? '{{ __('messages.shipments.less_filters') }}' : '{{ __('messages.shipments.more_filters') }}'">{{ __('messages.shipments.more_filters') }}</span>
                        <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <a href="{{ route('shipments.index') }}" class="py-2 px-3 rounded-xl bg-[#1e294b] hover:bg-[#25335e] text-slate-400 hover:text-white font-medium text-xs border border-[#1e294b] transition">
                        {{ __('messages.shipments.reset_btn') }}
                    </a>
                </div>
            </div>

            <!-- Advanced Filter Drawer -->
            <div x-show="expanded" x-cloak class="pt-3 border-t border-[#1e294b] grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Origin Hub -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('messages.shipments.origin_hub') }}</label>
                    <select name="origin_location_id" class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                        <option value="">{{ __('messages.shipments.all_origins') }}</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ request('origin_location_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->city }} ({{ $loc->code }}) - {{ $loc->country }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Destination Hub -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('messages.shipments.destination_hub') }}</label>
                    <select name="destination_location_id" class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                        <option value="">{{ __('messages.shipments.all_destinations') }}</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ request('destination_location_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->city }} ({{ $loc->code }}) - {{ $loc->country }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Delay Severity -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('messages.shipments.delay_severity') }}</label>
                    <select name="delay_severity" class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                        <option value="">{{ __('messages.shipments.all_severities') }}</option>
                        <option value="NONE" {{ request('delay_severity') === 'NONE' ? 'selected' : '' }}>{{ __('messages.severity.none') }}</option>
                        <option value="LOW" {{ request('delay_severity') === 'LOW' ? 'selected' : '' }}>{{ __('messages.severity.low') }}</option>
                        <option value="MEDIUM" {{ request('delay_severity') === 'MEDIUM' ? 'selected' : '' }}>{{ __('messages.severity.medium') }}</option>
                        <option value="HIGH" {{ request('delay_severity') === 'HIGH' ? 'selected' : '' }}>{{ __('messages.severity.high') }}</option>
                    </select>
                </div>

                <!-- Open Exceptions Filter -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('messages.shipments.incident_exception') }}</label>
                    <select name="has_exceptions" class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                        <option value="">{{ __('messages.shipments.all_incident_statuses') }}</option>
                        <option value="1" {{ request('has_exceptions') === '1' ? 'selected' : '' }}>{{ __('messages.shipments.active_incidents') }}</option>
                        <option value="0" {{ request('has_exceptions') === '0' ? 'selected' : '' }}>{{ __('messages.shipments.incident_free') }}</option>
                    </select>
                </div>

                <!-- Expected Departure Range -->
                <div class="sm:col-span-2 grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('messages.shipments.etd_from') }}</label>
                        <input type="date" name="departure_from" value="{{ request('departure_from') }}"
                            class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('messages.shipments.etd_to') }}</label>
                        <input type="date" name="departure_to" value="{{ request('departure_to') }}"
                            class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                    </div>
                </div>

                <!-- Arrival Date Range -->
                <div class="sm:col-span-2 grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('messages.shipments.eta_from') }}</label>
                        <input type="date" name="arrival_from" value="{{ request('arrival_from') }}"
                            class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('messages.shipments.eta_to') }}</label>
                        <input type="date" name="arrival_to" value="{{ request('arrival_to') }}"
                            class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Shipments Table -->
    <div class="bg-[#101935] rounded-2xl border border-[#1e294b] shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#080f24] text-slate-400 uppercase tracking-wider font-semibold border-b border-[#1e294b]">
                    <tr>
                        <th class="py-3.5 px-4">{{ __('messages.shipments.col_shipment') }}</th>
                        @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
                            <th class="py-3.5 px-4">{{ __('messages.shipments.col_customer') }}</th>
                        @endif
                        <th class="py-3.5 px-4">{{ __('messages.shipments.col_carrier_cargo') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.shipments.col_route') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.shipments.col_current_location') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.shipments.col_status') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.shipments.col_schedule') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.shipments.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1e294b]/60">
                    @forelse($shipments as $s)
                        <tr class="hover:bg-[#162244]/50 transition">
                            <td class="py-4 px-4 font-mono font-bold text-pink-400">
                                <a href="{{ route('shipments.show', $s) }}" class="hover:underline flex items-center space-x-1.5">
                                    <span>{{ $s->shipment_number }}</span>
                                </a>
                                <div class="text-[10px] text-slate-400 font-sans font-normal mt-0.5">
                                    {{ __('messages.shipments.items_count', ['count' => $s->items_count]) }} &bull; {{ number_format($s->weight, 0) }} kg
                                </div>
                            </td>

                            @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
                                <td class="py-4 px-4">
                                    <div class="font-semibold text-slate-200">{{ $s->customer->name }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $s->customer->email }}</div>
                                </td>
                            @endif

                            <td class="py-4 px-4">
                                <div class="font-bold text-white">{{ $s->carrier->name }}</div>
                                <div class="text-[11px] text-slate-400 max-w-xs truncate">{{ $s->cargo_type }}</div>
                            </td>

                            <td class="py-4 px-4">
                                <div class="flex items-center space-x-1.5 text-slate-200">
                                    <span class="font-semibold">{{ $s->originLocation->city }}</span>
                                    <span class="text-slate-500">&rarr;</span>
                                    <span class="font-semibold">{{ $s->destinationLocation->city }}</span>
                                </div>
                                <div class="text-[10px] text-slate-500">
                                    {{ $s->originLocation->code }} &bull; {{ $s->destinationLocation->code }}
                                </div>
                            </td>

                            <!-- Current Location from latest valid event -->
                            <td class="py-4 px-4">
                                <div class="text-xs font-semibold text-white flex items-center space-x-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-pink-400 shrink-0"></span>
                                    <span class="truncate max-w-[180px]">{{ $s->current_location_description }}</span>
                                </div>
                                @php $locEv = $s->latestLocationEvent(); @endphp
                                @if($locEv)
                                    <div class="text-[10px] text-slate-500 mt-0.5">{{ $locEv->event_time->diffForHumans() }}</div>
                                @endif
                            </td>

                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider
                                    @if($s->current_status === 'delivered') bg-emerald-950 text-emerald-400 border border-emerald-800
                                    @elseif($s->current_status === 'exception' || $s->is_delayed) bg-rose-950 text-rose-400 border border-rose-800
                                    @elseif($s->current_status === 'customs_hold') bg-amber-950 text-amber-400 border border-amber-800
                                    @elseif($s->current_status === 'picked_up') bg-indigo-950 text-indigo-400 border border-indigo-800
                                    @elseif($s->current_status === 'booked') bg-blue-950 text-blue-400 border border-blue-800
                                    @else bg-pink-950 text-pink-300 border border-pink-800 @endif">
                                    {{ str_replace('_', ' ', $s->current_status) }}
                                </span>
                                @if($s->is_delayed)
                                    <div class="mt-1 text-[10px] font-bold text-rose-400 flex items-center space-x-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        <span>+{{ $s->delay_hours }}h ({{ $s->delay_severity }})</span>
                                    </div>
                                @endif
                                @if($s->open_exceptions_count > 0)
                                    <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded bg-amber-950 text-amber-300 text-[9px] font-bold border border-amber-800">
                                        {{ __('messages.shipments.incidents_badge', ['count' => $s->open_exceptions_count]) }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-4 px-4 text-slate-300">
                                <div><span class="text-slate-500">ETA:</span> {{ $s->estimated_arrival ? $s->estimated_arrival->format('M d, Y') : $s->expected_arrival->format('M d, Y') }}</div>
                                <div class="text-[10px] text-slate-500">ETD: {{ $s->expected_departure->format('M d') }}</div>
                            </td>

                            <td class="py-4 px-4 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('shipments.show', $s) }}" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-pink-600 hover:bg-pink-500 text-white font-semibold text-xs shadow shadow-pink-500/20 transition">
                                    {{ __('messages.shipments.view_timeline') }}
                                </a>
                                @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
                                    <a href="{{ route('shipments.edit', $s) }}" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#1e294b] hover:bg-[#25335e] text-slate-300 text-xs border border-[#1e294b] transition">
                                        {{ __('messages.shipments.edit') }}
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500">
                                {{ __('messages.shipments.empty_results') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($shipments->hasPages())
            <div class="p-4 border-t border-[#1e294b] bg-[#080f24]">
                {{ $shipments->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
