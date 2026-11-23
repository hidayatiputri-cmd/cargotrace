@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-white">Immutable Platform Audit Logs</h1>
            <p class="text-xs text-slate-400">Regulatory compliance, event traceability, data mutations & actor audit trail</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800 shadow">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Action Type</label>
                <input type="text" name="action" value="{{ request('action') }}" placeholder="e.g. create_shipment, create_tracking_event"
                    class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:ring-1 focus:ring-cyan-500">
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Entity Type</label>
                <input type="text" name="entity_type" value="{{ request('entity_type') }}" placeholder="e.g. Shipment, ShipmentException"
                    class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:ring-1 focus:ring-cyan-500">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs">Filter Logs</button>
                <a href="{{ route('admin.audit-logs.index') }}" class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs border border-slate-700">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-slate-900/90 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Timestamp</th>
                    <th class="py-3 px-4">Actor</th>
                    <th class="py-3 px-4">Action</th>
                    <th class="py-3 px-4">Target Entity</th>
                    <th class="py-3 px-4">Payload Diff (Old &rarr; New)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 font-mono text-[11px]">
                @forelse($auditLogs as $log)
                    <tr class="hover:bg-slate-800/40">
                        <td class="py-3.5 px-4 text-slate-400 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="py-3.5 px-4 font-sans font-bold text-white whitespace-nowrap">
                            {{ $log->user?->name ?? 'System' }}
                            @if($log->user)
                                <div class="text-[10px] text-slate-500 font-normal">{{ $log->user->role }}</div>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-cyan-400 font-bold whitespace-nowrap">{{ $log->action }}</td>
                        <td class="py-3.5 px-4 text-slate-300 whitespace-nowrap">{{ $log->entity_type }} #{{ $log->entity_id }}</td>
                        <td class="py-3.5 px-4 font-sans text-xs max-w-md">
                            @if($log->old_values)
                                <div class="text-[10px] text-rose-400 line-clamp-1"><strong class="text-slate-500">OLD:</strong> {{ json_encode($log->old_values) }}</div>
                            @endif
                            @if($log->new_values)
                                <div class="text-[10px] text-emerald-400 line-clamp-1"><strong class="text-slate-500">NEW:</strong> {{ json_encode($log->new_values) }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-slate-500 font-sans">No audit records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($auditLogs->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-950">{{ $auditLogs->links() }}</div>
        @endif
    </div>
</div>
@endsection
