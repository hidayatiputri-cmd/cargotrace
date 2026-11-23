@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center space-x-3">
        <a href="{{ route('shipments.index') }}" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-extrabold text-white">Create New Freight Shipment</h1>
            <p class="text-xs text-slate-400">Register multi-modal consignment, assigned carrier, schedule and manifest items</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-slate-900/90 rounded-2xl border border-slate-800 shadow-xl p-6 sm:p-8">
        <form action="{{ route('shipments.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Shipment Number & Customer -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Shipment Number <span class="text-slate-500 font-normal">(Optional, auto-generated if blank)</span>
                    </label>
                    <input type="text" name="shipment_number" value="{{ old('shipment_number') }}" placeholder="e.g. CT-2026-SG-RT99"
                        class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    @error('shipment_number') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Consignee Customer *</label>
                    <select name="customer_id" required class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="">Select Customer Account</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Carrier & Cargo Type -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Carrier Line *</label>
                    <select name="carrier_id" required class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="">Select Maritime/Air Carrier</option>
                        @foreach($carriers as $car)
                            <option value="{{ $car->id }}" {{ old('carrier_id') == $car->id ? 'selected' : '' }}>
                                {{ $car->name }} ({{ $car->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('carrier_id') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Cargo Type / Classification *</label>
                    <input type="text" name="cargo_type" value="{{ old('cargo_type') }}" required placeholder="e.g. Semiconductor Cleanroom Optics"
                        class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    @error('cargo_type') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Origin & Destination -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Origin Port/Hub *</label>
                    <select name="origin_location_id" required class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="">Select Origin Node</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('origin_location_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }} ({{ $loc->code }}) - {{ $loc->city }}
                            </option>
                        @endforeach
                    </select>
                    @error('origin_location_id') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Destination Port/Hub *</label>
                    <select name="destination_location_id" required class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="">Select Destination Node</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('destination_location_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }} ({{ $loc->code }}) - {{ $loc->city }}
                            </option>
                        @endforeach
                    </select>
                    @error('destination_location_id') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Weight, Volume, Status -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Total Weight (kg) *</label>
                    <input type="number" step="0.01" name="weight" value="{{ old('weight', 1000) }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Volume (CBM / m³) *</label>
                    <input type="number" step="0.01" name="volume" value="{{ old('volume', 25) }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Initial Status</label>
                    <select name="current_status" class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="booked">Booked</option>
                        <option value="in_transit">In-Transit</option>
                        <option value="customs_hold">Customs Hold</option>
                    </select>
                </div>
            </div>

            <!-- Expected Departure & Arrival -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Expected Departure (ETD) *</label>
                    <input type="datetime-local" name="expected_departure" value="{{ old('expected_departure', now()->format('Y-m-d\TH:i')) }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Expected Arrival (ETA) *</label>
                    <input type="datetime-local" name="expected_arrival" value="{{ old('expected_arrival', now()->addDays(7)->format('Y-m-d\TH:i')) }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Estimated Arrival (Optional)</label>
                    <input type="datetime-local" name="estimated_arrival" value="{{ old('estimated_arrival') }}"
                        class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                </div>
            </div>

            <!-- Initial Manifest Items -->
            <div class="pt-4 border-t border-slate-800">
                <h3 class="text-sm font-bold text-white mb-3">Manifest Cargo Items</h3>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 p-3 bg-slate-950/60 rounded-xl border border-slate-800">
                        <div class="sm:col-span-2">
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Item Name</label>
                            <input type="text" name="items[0][item_name]" placeholder="e.g. Optical Transceiver Tray" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-xs text-white focus:outline-none focus:ring-1 focus:ring-cyan-500">
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Quantity</label>
                            <input type="number" name="items[0][quantity]" value="1" min="1" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-xs text-white focus:outline-none focus:ring-1 focus:ring-cyan-500">
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Weight (kg)</label>
                            <input type="number" step="0.01" name="items[0][weight]" value="100" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-xs text-white focus:outline-none focus:ring-1 focus:ring-cyan-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                <a href="{{ route('shipments.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-bold text-xs shadow-lg shadow-cyan-600/20">
                    Create & Dispatch Shipment
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
