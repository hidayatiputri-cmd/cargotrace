@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-white">Locations & Global Hubs</h1>
            <p class="text-xs text-slate-400">Maritime seaports, air freight cargo terminals & inland transshipment nodes</p>
        </div>
        <button type="button" onclick="document.getElementById('locationModal').classList.remove('hidden')"
            class="px-4 py-2 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 hover:from-pink-400 hover:to-rose-500 text-white font-bold text-xs shadow-lg shadow-pink-600/25 transition">
            + Add Location
        </button>
    </div>

    <div class="bg-[#101935] rounded-2xl border border-[#1e294b] shadow-xl overflow-hidden">
        <table class="w-full text-left text-xs">
            <thead class="bg-[#080f24] text-slate-400 uppercase tracking-wider font-semibold border-b border-[#1e294b]">
                <tr>
                    <th class="py-3 px-4">Location Name</th>
                    <th class="py-3 px-4">Code</th>
                    <th class="py-3 px-4">City & Country</th>
                    <th class="py-3 px-4">Coordinates</th>
                    <th class="py-3 px-4">Origin / Dest Shipments</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#1e294b]/60">
                @foreach($locations as $loc)
                    <tr class="hover:bg-[#162244]">
                        <td class="py-3.5 px-4 font-bold text-white">{{ $loc->name }}</td>
                        <td class="py-3.5 px-4 font-mono font-bold text-pink-400">{{ $loc->code }}</td>
                        <td class="py-3.5 px-4 text-slate-300">{{ $loc->city }}, {{ $loc->country }}</td>
                        <td class="py-3.5 px-4 font-mono text-slate-400 text-[11px]">{{ $loc->latitude }}, {{ $loc->longitude }}</td>
                        <td class="py-3.5 px-4 text-slate-300">{{ $loc->origin_shipments_count }} orig / {{ $loc->destination_shipments_count }} dest</td>
                        <td class="py-3.5 px-4 text-right">
                            <form action="{{ route('admin.locations.destroy', $loc) }}" method="POST" onsubmit="return confirm('Delete location?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-rose-400 hover:text-rose-300">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if($locations->hasPages())
            <div class="p-4 border-t border-[#1e294b] bg-[#080f24]">{{ $locations->links() }}</div>
        @endif
    </div>
</div>

<!-- Modal: Add Location -->
<div id="locationModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-[#101935] border border-[#1e294b] rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
        <h3 class="text-base font-bold text-white mb-4">Add Port / Logistics Hub</h3>
        <form action="{{ route('admin.locations.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Hub Name</label>
                <input type="text" name="name" required placeholder="e.g. Port of Antwerp-Bruges" class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-1 focus:ring-pink-500">
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">UN/LOCODE</label>
                    <input type="text" name="code" required placeholder="BEANR" class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-1 focus:ring-pink-500">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">City</label>
                    <input type="text" name="city" required placeholder="Antwerp" class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-1 focus:ring-pink-500">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Country</label>
                    <input type="text" name="country" required placeholder="Belgium" class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-1 focus:ring-pink-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Latitude</label>
                    <input type="number" step="0.000001" name="latitude" placeholder="51.2194" class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-1 focus:ring-pink-500">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Longitude</label>
                    <input type="number" step="0.000001" name="longitude" placeholder="4.4025" class="w-full px-3 py-2 bg-[#080f24] border border-[#1e294b] rounded-xl text-xs text-white focus:ring-1 focus:ring-pink-500">
                </div>
            </div>
            <div class="pt-3 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('locationModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-[#1e294b] text-xs text-slate-300">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 hover:from-pink-400 hover:to-rose-500 text-white font-bold text-xs shadow-pink-600/20">Save Location</button>
            </div>
        </form>
    </div>
</div>
@endsection
