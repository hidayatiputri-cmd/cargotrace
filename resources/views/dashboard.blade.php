@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Top Welcome Banner with Role Context -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-slate-850 to-slate-900 border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10">
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-2xl font-extrabold text-white">Welcome, {{ $user->name }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider
                        @if($user->isAdmin()) bg-purple-950 text-purple-300 border border-purple-800
                        @elseif($user->isOperator()) bg-amber-950 text-amber-300 border border-amber-800
                        @else bg-cyan-950 text-cyan-300 border border-cyan-800 @endif">
                        {{ $user->role }}
                    </span>
                </div>
                <p class="text-sm text-slate-400 mt-1">
                    @if($user->isAdmin())
                        Platform Administrator &bull; Global fleet oversight, master carriers, hub nodes & immutable audit logs.
                    @elseif($user->isOperator())
                        Logistics Operator &bull; Multi-modal dispatch desk, tracking milestones, telemetry pings & exception management.
                    @else
                        Customer Consignee Portal &bull; End-to-end container tracking, route milestones, and ETA SLA visibility.
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if($user->isAdmin() || $user->isOperator())
                    <a href="{{ route('shipments.create') }}" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white shadow-lg shadow-cyan-500/25 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Create Shipment</span>
                    </a>
                @endif
                <a href="{{ route('shipments.index') }}" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl font-medium text-sm bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition">
                    <span>View Shipments Registry</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Role-Based Operational KPI Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Active Shipments -->
        <a href="{{ route('shipments.index') }}" class="bg-slate-900/80 hover:bg-slate-850 p-5 rounded-2xl border border-slate-800 shadow transition group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Shipments</span>
                <span class="p-2 rounded-xl bg-cyan-500/10 text-cyan-400 group-hover:scale-105 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </span>
            </div>
            <div class="mt-4 text-3xl font-extrabold text-cyan-400">{{ $metrics['active_shipments'] ?? $metrics['in_transit'] }}</div>
            <div class="mt-1 text-xs text-slate-400">Underway & not delivered</div>
        </a>

        <!-- Delayed Shipments -->
        <a href="{{ route('shipments.index', ['is_delayed' => 1]) }}" class="bg-slate-900/80 hover:bg-slate-850 p-5 rounded-2xl border border-slate-800 shadow transition group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Delayed Shipments</span>
                <span class="p-2 rounded-xl bg-rose-500/10 text-rose-400 group-hover:scale-105 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-4 text-3xl font-extrabold text-rose-400">{{ $metrics['delayed'] }}</div>
            <div class="mt-1 text-xs text-slate-400">Exceeding SLA threshold</div>
        </a>

        <!-- Open Exceptions -->
        <a href="{{ route('shipments.index', ['status' => 'exception']) }}" class="bg-slate-900/80 hover:bg-slate-850 p-5 rounded-2xl border border-slate-800 shadow transition group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Open Exceptions</span>
                <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 group-hover:scale-105 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <div class="mt-4 text-3xl font-extrabold text-amber-400">{{ $metrics['open_exceptions'] }}</div>
            <div class="mt-1 text-xs text-slate-400">Incidents under review</div>
        </a>

        <!-- On-Time SLA Rate -->
        <div class="bg-slate-900/80 p-5 rounded-2xl border border-slate-800 shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">On-Time SLA Rate</span>
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-4 text-3xl font-extrabold text-emerald-400">{{ $metrics['on_time_rate'] }}%</div>
            <div class="mt-1 text-xs text-slate-400">Performance compliance</div>
        </div>

        <!-- Total Bookings -->
        <a href="{{ route('shipments.index') }}" class="bg-slate-900/80 hover:bg-slate-850 p-5 rounded-2xl border border-slate-800 shadow transition group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Lifetime</span>
                <span class="p-2 rounded-xl bg-blue-500/10 text-blue-400 group-hover:scale-105 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <div class="mt-4 text-3xl font-extrabold text-white">{{ $metrics['total_shipments'] }}</div>
            <div class="mt-1 text-xs text-slate-400">{{ $metrics['delivered'] }} delivered safely</div>
        </a>
    </div>

    <!-- Status Distribution & Lifecycle Overview -->
    @if(!empty($metrics['status_distribution']))
        <div class="bg-slate-900/90 p-5 rounded-2xl border border-slate-800 shadow-xl">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Lifecycle Status Breakdown</span>
                <span class="text-[11px] text-cyan-400 font-mono">{{ $metrics['total_shipments'] }} Total Bookings</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-6 gap-3">
                @foreach([
                    'created' => ['label' => 'CREATED', 'color' => 'slate', 'bg' => 'bg-slate-800 text-slate-300'],
                    'booked' => ['label' => 'BOOKED', 'color' => 'blue', 'bg' => 'bg-blue-950 text-blue-300 border border-blue-800'],
                    'picked_up' => ['label' => 'PICKED UP', 'color' => 'indigo', 'bg' => 'bg-indigo-950 text-indigo-300 border border-indigo-800'],
                    'in_transit' => ['label' => 'IN TRANSIT', 'color' => 'cyan', 'bg' => 'bg-cyan-950 text-cyan-300 border border-cyan-800'],
                    'exception' => ['label' => 'EXCEPTION', 'color' => 'rose', 'bg' => 'bg-rose-950 text-rose-300 border border-rose-800'],
                    'delivered' => ['label' => 'DELIVERED', 'color' => 'emerald', 'bg' => 'bg-emerald-950 text-emerald-300 border border-emerald-800'],
                ] as $key => $meta)
                    @php
                        $count = $metrics['status_distribution'][$key] ?? 0;
                    @endphp
                    <a href="{{ route('shipments.index', ['status' => $key]) }}" class="p-3 rounded-xl bg-slate-950/70 border border-slate-800 hover:border-slate-700 transition flex flex-col items-center text-center">
                        <span class="text-lg font-black font-mono text-white">{{ $count }}</span>
                        <span class="mt-1 px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase {{ $meta['bg'] }}">
                            {{ $meta['label'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Main Grid: Recent Shipments & Operational Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Shipments List (2 cols) -->
        <div class="lg:col-span-2 bg-slate-900/80 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
            <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-white">Recent Tracked Consignments</h2>
                    <p class="text-xs text-slate-400">Live operational status and latest checkpoint pings</p>
                </div>
                <a href="{{ route('shipments.index') }}" class="text-xs font-semibold text-cyan-400 hover:text-cyan-300">View All &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Shipment #</th>
                            <th class="py-3 px-4">Carrier & Cargo</th>
                            <th class="py-3 px-4">Corridor</th>
                            <th class="py-3 px-4">Status & Health</th>
                            <th class="py-3 px-4">Target ETA</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($recentShipments as $s)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-cyan-400">
                                    <a href="{{ route('shipments.show', $s) }}" class="hover:underline">{{ $s->shipment_number }}</a>
                                    @if($user->isAdmin() || $user->isOperator())
                                        <div class="text-[10px] text-slate-400 font-sans font-normal">{{ $s->customer->name }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-white">{{ $s->carrier->name }} ({{ $s->carrier->code }})</div>
                                    <div class="text-[11px] text-slate-400 truncate max-w-xs">{{ $s->cargo_type }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-slate-200">{{ $s->originLocation->city }} &rarr; {{ $s->destinationLocation->city }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $s->originLocation->code }} &bull; {{ $s->destinationLocation->code }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider
                                        @if($s->current_status === 'delivered') bg-emerald-950 text-emerald-400 border border-emerald-800
                                        @elseif($s->current_status === 'exception' || $s->is_delayed) bg-rose-950 text-rose-400 border border-rose-800
                                        @elseif($s->current_status === 'customs_hold') bg-amber-950 text-amber-400 border border-amber-800
                                        @elseif($s->current_status === 'picked_up') bg-indigo-950 text-indigo-400 border border-indigo-800
                                        @elseif($s->current_status === 'booked') bg-blue-950 text-blue-400 border border-blue-800
                                        @else bg-slate-800 text-slate-300 border border-slate-700 @endif">
                                        {{ str_replace('_', ' ', $s->current_status) }}
                                    </span>
                                    @if($s->is_delayed)
                                        <span class="block mt-0.5 text-[10px] font-semibold text-rose-400">+{{ $s->delay_hours }}h ({{ $s->delay_severity }})</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-slate-300">
                                    {{ $s->estimated_arrival ? $s->estimated_arrival->format('M d, H:i') : $s->expected_arrival->format('M d, H:i') }}
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="{{ route('shipments.show', $s) }}" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition">
                                        Track
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-500">No shipments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sidebar: Active Exceptions & Admin Audit Stream (1 col) -->
        <div class="space-y-6">
            <!-- Exceptions Panel -->
            <div class="bg-slate-900/80 rounded-2xl border border-slate-800 shadow-xl p-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                        <span>Active Exceptions</span>
                    </h3>
                    <span class="text-xs text-rose-400 font-semibold">{{ $metrics['open_exceptions'] }} Open</span>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse($recentExceptions as $exc)
                        <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 hover:border-slate-700 transition">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs font-bold text-cyan-400">
                                    <a href="{{ route('shipments.show', $exc->shipment) }}" class="hover:underline">#{{ $exc->shipment->shipment_number }}</a>
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold
                                    @if(strtoupper($exc->severity) === 'CRITICAL' || strtoupper($exc->severity) === 'HIGH') bg-rose-950 text-rose-300 border border-rose-800
                                    @elseif(strtoupper($exc->severity) === 'MEDIUM') bg-amber-950 text-amber-300 border border-amber-800
                                    @else bg-blue-950 text-blue-300 border border-blue-800 @endif">
                                    {{ strtoupper($exc->severity) }}
                                </span>
                            </div>
                            <div class="mt-1 text-xs font-semibold text-slate-200 capitalize">{{ str_replace('_', ' ', $exc->type) }}</div>
                            <p class="mt-1 text-[11px] text-slate-400 line-clamp-2">{{ $exc->description }}</p>
                            <div class="mt-2 text-[10px] text-slate-500 flex items-center justify-between">
                                <span>Detected: {{ $exc->detected_at->diffForHumans() }}</span>
                                <span class="uppercase font-mono text-amber-400 font-semibold text-[10px]">{{ $exc->status }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-slate-500">No active incidents reported. Fleet operating nominal.</div>
                    @endforelse
                </div>
            </div>

            <!-- Admin Live Audit Stream -->
            @if($user->isAdmin() && !empty($adminStats['recent_audit_logs']))
                <div class="bg-slate-900/80 rounded-2xl border border-slate-800 shadow-xl p-5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <h3 class="text-sm font-bold text-white">Immutable Audit Trail</h3>
                        <a href="{{ route('admin.audit-logs.index') }}" class="text-xs text-cyan-400 hover:underline">Full Log &rarr;</a>
                    </div>
                    <div class="mt-4 space-y-2.5">
                        @foreach($adminStats['recent_audit_logs'] as $log)
                            <div class="p-2.5 rounded-lg bg-slate-950 border border-slate-850 text-xs flex items-start justify-between">
                                <div>
                                    <span class="font-mono text-cyan-400 font-bold text-[11px]">{{ $log->action }}</span>
                                    <div class="text-[10px] text-slate-400">By {{ $log->user?->name ?? 'System' }} &bull; {{ $log->entity_type }} #{{ $log->entity_id }}</div>
                                </div>
                                <span class="text-[10px] text-slate-500">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
