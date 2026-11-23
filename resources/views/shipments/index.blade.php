@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white">Global Shipments Registry</h1>
            <p class="text-xs text-slate-400 mt-1">
                @if(auth()->user()->isCustomer())
                    Displaying multi-modal freight shipments assigned to your organization.
                @else
                    Full visibility into worldwide freight shipments, transshipments, and carriers.
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

    <!-- Filter Bar -->
    <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800 shadow-xl">
        <form method="GET" action="{{ route('shipments.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Search -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Shipment # or Cargo..."
                    class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-cyan-500 focus:outline-none">
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="booked" {{ request('status') === 'booked' ? 'selected' : '' }}>Booked</option>
                    <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>In-Transit</option>
                    <option value="customs_hold" {{ request('status') === 'customs_hold' ? 'selected' : '' }}>Customs Hold</option>
                    <option value="exception" {{ request('status') === 'exception' ? 'selected' : '' }}>Exception</option>
                    <option value="out_for_delivery" {{ request('status') === 'out_for_delivery' ? 'selected' : '' }}>Out for Delivery</option>
                    <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                </select>
            </div>

            <!-- Delay Filter -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Schedule Delay</label>
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

            <!-- Filter Buttons -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow transition">
                    Filter
                </button>
                <a href="{{ route('shipments.index') }}" class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium text-xs border border-slate-700 transition">
                    Reset
                </a>
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
                            <th class="py-3.5 px-4">Customer</th>
                        @endif
                        <th class="py-3.5 px-4">Carrier & Cargo</th>
                        <th class="py-3.5 px-4">Origin & Destination</th>
                        <th class="py-3.5 px-4">Status & Health</th>
                        <th class="py-3.5 px-4">Schedule (Dep / Arr)</th>
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

                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider
                                    @if($s->current_status === 'delivered') bg-emerald-950 text-emerald-400 border border-emerald-800
                                    @elseif($s->current_status === 'exception' || $s->is_delayed) bg-rose-950 text-rose-400 border border-rose-800
                                    @elseif($s->current_status === 'customs_hold') bg-amber-950 text-amber-400 border border-amber-800
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
                                <div><span class="text-slate-500">ETD:</span> {{ $s->expected_departure->format('M d, Y') }}</div>
                                <div><span class="text-slate-500">ETA:</span> {{ $s->estimated_arrival ? $s->estimated_arrival->format('M d, Y') : $s->expected_arrival->format('M d, Y') }}</div>
                            </td>

                            <td class="py-4 px-4 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('shipments.show', $s) }}" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-semibold text-xs transition">
                                    View Live Map
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
                            <td colspan="7" class="py-8 text-center text-slate-500">
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
                {{ $shipments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
