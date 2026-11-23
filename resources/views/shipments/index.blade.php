@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <h1 class="text-2xl font-extrabold text-white">Global Shipments Registry</h1>
                @if(auth()->user()->isCustomer())
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-cyan-950 text-cyan-300 border border-cyan-800">
                        Consignee Private Fleet
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-slate-800 text-slate-300 border border-slate-700">
                        Global Dispatch Grid
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-400 mt-1">
                @if(auth()->user()->isCustomer())
                    Displaying multi-modal freight shipments assigned to your organization. Multi-tenant access strictly isolated.
                @else
                    Full visibility into worldwide freight shipments, tracking events, transshipments, and carriers.
                @endif
            </p>
        </div>

        @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
            <a href="{{ route('shipments.create') }}" class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white shadow-lg shadow-cyan-500/25 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Register New Shipment</span>
            </a>
        @endif
    </div>

    <!-- Advanced Filter Console -->
    <div class="bg-slate-900/90 p-5 rounded-2xl border border-slate-800 shadow-xl" x-data="{ expanded: {{ request()->hasAny(['origin_location_id', 'destination_location_id', 'delay_severity', 'has_exceptions', 'departure_from', 'departure_to', 'arrival_from', 'arrival_to']) ? 'true' : 'false' }} }">
        <form method="GET" action="{{ route('shipments.index') }}" class="space-y-4">
            <!-- Primary Filter Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Shipment #, cargo or customer..."
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                </div>

                <!-- Status Filter with full Lifecycle -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Lifecycle Status</label>
                    <select name="status" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="">All Lifecycle Stages</option>
                        <option value="created" {{ request('status') === 'created' ? 'selected' : '' }}>CREATED (Registered)</option>
                        <option value="booked" {{ request('status') === 'booked' ? 'selected' : '' }}>BOOKED (Carrier Confirmed)</option>
                        <option value="picked_up" {{ request('status') === 'picked_up' ? 'selected' : '' }}>PICKED_UP (Origin Cleared)</option>
                        <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>IN_TRANSIT (Underway)</option>
                        <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>DELIVERED (Finalized)</option>
                        <option value="exception" {{ request('status') === 'exception' ? 'selected' : '' }}>EXCEPTION (Active Incident)</option>
                        <option value="customs_hold" {{ request('status') === 'customs_hold' ? 'selected' : '' }}>CUSTOMS_HOLD</option>
                    </select>
                </div>

                <!-- Schedule Health / Delay -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Schedule Health</label>
                    <select name="is_delayed" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="">All Schedules</option>
                        <option value="1" {{ request('is_delayed') === '1' ? 'selected' : '' }}>Delayed Only</option>
                        <option value="0" {{ request('is_delayed') === '0' ? 'selected' : '' }}>On-Time Only</option>
                    </select>
                </div>

                <!-- Carrier Filter -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Carrier</label>
                    <select name="carrier_id" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="">All Carriers</option>
                        @foreach($carriers as $car)
                            <option value="{{ $car->id }}" {{ request('carrier_id') == $car->id ? 'selected' : '' }}>
                                {{ $car->name }} ({{ $car->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action & Expand Buttons -->
                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold text-xs shadow-lg shadow-cyan-500/20 transition">
                        Filter
                    </button>
                    <button type="button" @click="expanded = !expanded" class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs border border-slate-700 transition flex items-center space-x-1" title="Toggle Advanced Filter Parameters">
                        <span x-text="expanded ? 'Less' : 'More'">More</span>
                        <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <a href="{{ route('shipments.index') }}" class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white font-medium text-xs border border-slate-700 transition">
                        Reset
                    </a>
                </div>
            </div>

            <!-- Advanced Filter Drawer -->
            <div x-show="expanded" x-cloak class="pt-3 border-t border-slate-800 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Origin Hub -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Origin Hub</label>
                    <select name="origin_location_id" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="">All Origin Hubs</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ request('origin_location_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->city }} ({{ $loc->code }}) - {{ $loc->country }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Destination Hub -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Destination Hub</label>
                    <select name="destination_location_id" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="">All Destination Hubs</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ request('destination_location_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->city }} ({{ $loc->code }}) - {{ $loc->country }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Delay Severity -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Delay Severity</label>
                    <select name="delay_severity" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="">All Severity Tiers</option>
                        <option value="NONE" {{ request('delay_severity') === 'NONE' ? 'selected' : '' }}>NONE (On Schedule)</option>
                        <option value="LOW" {{ request('delay_severity') === 'LOW' ? 'selected' : '' }}>LOW (&le; 6h excess)</option>
                        <option value="MEDIUM" {{ request('delay_severity') === 'MEDIUM' ? 'selected' : '' }}>MEDIUM (6h - 24h excess)</option>
                        <option value="HIGH" {{ request('delay_severity') === 'HIGH' ? 'selected' : '' }}>HIGH (&gt; 24h excess)</option>
                    </select>
                </div>

                <!-- Open Exceptions Filter -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Incident / Exception</label>
                    <select name="has_exceptions" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="">All Statuses</option>
                        <option value="1" {{ request('has_exceptions') === '1' ? 'selected' : '' }}>Active Incidents / Exceptions</option>
                        <option value="0" {{ request('has_exceptions') === '0' ? 'selected' : '' }}>Incident-Free</option>
                    </select>
                </div>

                <!-- Expected Departure Range -->
                <div class="sm:col-span-2 grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">ETD From</label>
                        <input type="date" name="departure_from" value="{{ request('departure_from') }}"
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">ETD To</label>
                        <input type="date" name="departure_to" value="{{ request('departure_to') }}"
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    </div>
                </div>

                <!-- Arrival Date Range -->
                <div class="sm:col-span-2 grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">ETA From</label>
                        <input type="date" name="arrival_from" value="{{ request('arrival_from') }}"
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">ETA To</label>
                        <input type="date" name="arrival_to" value="{{ request('arrival_to') }}"
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Shipments Table -->
    <div class="bg-slate-900/90 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">Shipment #</th>
                        @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
                            <th class="py-3.5 px-4">Customer Consignee</th>
                        @endif
                        <th class="py-3.5 px-4">Carrier & Cargo</th>
                        <th class="py-3.5 px-4">Origin & Destination</th>
                        <th class="py-3.5 px-4">Current Location</th>
                        <th class="py-3.5 px-4">Lifecycle Status</th>
                        <th class="py-3.5 px-4">Schedule (ETA)</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($shipments as $s)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-4 px-4 font-mono font-bold text-cyan-400">
                                <a href="{{ route('shipments.show', $s) }}" class="hover:underline flex items-center space-x-1.5">
                                    <span>{{ $s->shipment_number }}</span>
                                </a>
                                <div class="text-[10px] text-slate-400 font-sans font-normal mt-0.5">
                                    {{ $s->items_count }} items &bull; {{ number_format($s->weight, 0) }} kg
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
                                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 shrink-0"></span>
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
                                    @else bg-cyan-950 text-cyan-400 border border-cyan-800 @endif">
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
                                        {{ $s->open_exceptions_count }} Incident(s)
                                    </span>
                                @endif
                            </td>

                            <td class="py-4 px-4 text-slate-300">
                                <div><span class="text-slate-500">ETA:</span> {{ $s->estimated_arrival ? $s->estimated_arrival->format('M d, Y') : $s->expected_arrival->format('M d, Y') }}</div>
                                <div class="text-[10px] text-slate-500">ETD: {{ $s->expected_departure->format('M d') }}</div>
                            </td>

                            <td class="py-4 px-4 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('shipments.show', $s) }}" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-semibold text-xs transition">
                                    View Timeline
                                </a>
                                @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
                                    <a href="{{ route('shipments.edit', $s) }}" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs border border-slate-700 transition">
                                        Edit
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500">
                                No shipments found matching the criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($shipments->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-950">
                {{ $shipments->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
