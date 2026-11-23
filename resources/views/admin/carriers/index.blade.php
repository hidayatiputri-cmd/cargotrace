@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-white">Carriers Master Directory</h1>
            <p class="text-xs text-slate-400">Manage shipping lines, freight airlines, and rail logistics partners</p>
        </div>
        <button type="button" onclick="document.getElementById('carrierModal').classList.remove('hidden')"
            class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg shadow-cyan-600/25 transition">
            + Add Carrier
        </button>
    </div>

    <div class="bg-slate-900/90 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Carrier Name</th>
                    <th class="py-3 px-4">Code</th>
                    <th class="py-3 px-4">Contact Info</th>
                    <th class="py-3 px-4">Active Shipments</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @foreach($carriers as $carrier)
                    <tr class="hover:bg-slate-800/40">
                        <td class="py-3.5 px-4 font-bold text-white">{{ $carrier->name }}</td>
                        <td class="py-3.5 px-4 font-mono font-bold text-cyan-400">{{ $carrier->code }}</td>
                        <td class="py-3.5 px-4 text-slate-300">{{ $carrier->contact_info }}</td>
                        <td class="py-3.5 px-4 font-bold text-slate-200">{{ $carrier->shipments_count }}</td>
                        <td class="py-3.5 px-4">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $carrier->status === 'active' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-slate-800 text-slate-400' }}">
                                {{ $carrier->status }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <form action="{{ route('admin.carriers.destroy', $carrier) }}" method="POST" onsubmit="return confirm('Delete carrier?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-rose-400 hover:text-rose-300">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if($carriers->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-950">{{ $carriers->links() }}</div>
        @endif
    </div>
</div>

<!-- Modal: Add Carrier -->
<div id="carrierModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
        <h3 class="text-base font-bold text-white mb-4">Add Master Carrier</h3>
        <form action="{{ route('admin.carriers.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Carrier Name</label>
                <input type="text" name="name" required placeholder="e.g. Evergreen Marine" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-1 focus:ring-cyan-500">
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Carrier Code</label>
                <input type="text" name="code" required placeholder="e.g. EMCU" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-1 focus:ring-cyan-500">
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Contact Info</label>
                <input type="text" name="contact_info" placeholder="support@evergreen.com | +886 2 2501 1111" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-1 focus:ring-cyan-500">
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-1 focus:ring-cyan-500">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="pt-3 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('carrierModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-800 text-xs text-slate-300">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-cyan-600 text-white font-bold text-xs">Save Carrier</button>
            </div>
        </form>
    </div>
</div>
@endsection
