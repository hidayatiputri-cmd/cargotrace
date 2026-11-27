@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center space-x-3">
        <a href="{{ route('shipments.index') }}" class="p-2 rounded-xl bg-[#33215c] hover:bg-[#3d276e] text-slate-300 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-extrabold text-white">Create New Freight Consignment</h1>
            <p class="text-xs text-slate-400">Initiate shipment lifecycle: <span class="font-mono text-pink-400">CREATED &rarr; BOOKED &rarr; PICKED_UP &rarr; IN_TRANSIT &rarr; DELIVERED</span></p>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-950/80 border border-rose-800 text-rose-300 text-xs shadow-lg space-y-1">
            <div class="font-bold flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-rose-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>Please fix the following validation errors:</span>
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Card -->
    <div class="bg-[#1c1236] rounded-2xl border border-[#33215c] shadow-2xl p-6 sm:p-8">
        <form action="{{ route('shipments.store') }}" method="POST" id="shipmentForm" class="space-y-6">
            @csrf

            <!-- Section 1: Parties & Routing -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-pink-400 mb-3 flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-pink-500"></span>
                    <span>1. Consignment Parties & Routing</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                            Shipment Number <span class="text-slate-500 font-normal">(Auto-generated if left blank)</span>
                        </label>
                        <input type="text" name="shipment_number" value="{{ old('shipment_number') }}" placeholder="e.g. CT-2026-SG-RT09"
                            class="w-full px-3.5 py-2.5 bg-[#120b24] border border-[#33215c] rounded-xl text-xs font-mono text-pink-300 placeholder-slate-500 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Consignee Customer *</label>
                        <select name="customer_id" required class="w-full px-3.5 py-2.5 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                            <option value="">Select Customer Account</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Carrier Line *</label>
                        <select name="carrier_id" required class="w-full px-3.5 py-2.5 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                            <option value="">Select Carrier</option>
                            @foreach($carriers as $car)
                                <option value="{{ $car->id }}" {{ old('carrier_id') == $car->id ? 'selected' : '' }}>
                                    {{ $car->name }} ({{ $car->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Origin Port/Hub *</label>
                        <select name="origin_location_id" required class="w-full px-3.5 py-2.5 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                            <option value="">Select Origin Node</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('origin_location_id') == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->name }} ({{ $loc->code }}) - {{ $loc->city }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Destination Port/Hub *</label>
                        <select name="destination_location_id" required class="w-full px-3.5 py-2.5 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                            <option value="">Select Destination Node</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('destination_location_id') == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->name }} ({{ $loc->code }}) - {{ $loc->city }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Specifications & Schedule -->
            <div class="pt-4 border-t border-[#33215c]">
                <h3 class="text-xs font-bold uppercase tracking-wider text-pink-400 mb-3 flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-pink-500"></span>
                    <span>2. Cargo Specifications & Lifecycle Status</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Cargo Classification *</label>
                        <input type="text" name="cargo_type" value="{{ old('cargo_type') }}" required placeholder="e.g. Semiconductor Wafers & Sensors"
                            class="w-full px-3.5 py-2.5 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Total Weight (kg) *</label>
                        <input type="number" step="0.01" name="weight" id="totalWeight" value="{{ old('weight', 1000) }}" required
                            class="w-full px-3.5 py-2.5 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Total Volume (m³) *</label>
                        <input type="number" step="0.01" name="volume" value="{{ old('volume', 25) }}" required
                            class="w-full px-3.5 py-2.5 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Initial Lifecycle Status *</label>
                        <select name="current_status" required class="w-full px-3.5 py-2.5 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-pink-400 font-bold focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                            <option value="created" {{ old('current_status', 'created') === 'created' ? 'selected' : '' }}>CREATED (Draft / Registered)</option>
                            <option value="booked" {{ old('current_status') === 'booked' ? 'selected' : '' }}>BOOKED (Carrier Slot Confirmed)</option>
                        </select>
                        <p class="text-[10px] text-slate-500 mt-1">Lifecycle progresses sequentially: CREATED &rarr; BOOKED &rarr; PICKED_UP &rarr; IN_TRANSIT &rarr; DELIVERED</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Expected Departure (ETD) *</label>
                        <input type="datetime-local" name="expected_departure" value="{{ old('expected_departure', now()->format('Y-m-d\TH:i')) }}" required
                            class="w-full px-3.5 py-2.5 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Expected Arrival (ETA) *</label>
                        <input type="datetime-local" name="expected_arrival" value="{{ old('expected_arrival', now()->addDays(7)->format('Y-m-d\TH:i')) }}" required
                            class="w-full px-3.5 py-2.5 bg-[#120b24] border border-[#33215c] rounded-xl text-xs text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Section 3: Dynamic Cargo Items Manifes -->
            <div class="pt-4 border-t border-[#33215c]">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-pink-400 flex items-center space-x-2">
                            <span class="w-2 h-2 rounded-full bg-pink-500"></span>
                            <span>3. Cargo Items Manifest (shipment_items)</span>
                        </h3>
                        <p class="text-[11px] text-slate-400">Add detailed packaging breakdown, descriptions, and weights for customs</p>
                    </div>

                    <button type="button" onclick="addItemRow()"
                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-[#33215c] hover:bg-[#3d276e] text-pink-400 hover:text-pink-300 border border-[#33215c] text-xs font-bold transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Item Row</span>
                    </button>
                </div>

                <div class="overflow-x-auto rounded-xl border border-[#33215c]">
                    <table class="w-full text-left text-xs" id="itemsTable">
                        <thead class="bg-[#120b24] text-slate-400 uppercase tracking-wider font-semibold border-b border-[#33215c]">
                            <tr>
                                <th class="py-2.5 px-3 w-1/3">Item Name *</th>
                                <th class="py-2.5 px-3 w-24">Quantity *</th>
                                <th class="py-2.5 px-3 w-32">Weight (kg)</th>
                                <th class="py-2.5 px-3">Description / Specs</th>
                                <th class="py-2.5 px-3 text-right w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody" class="divide-y divide-[#33215c]">
                            <!-- Row 0 (Default) -->
                            <tr class="item-row bg-[#1c1236]">
                                <td class="py-2.5 px-3">
                                    <input type="text" name="items[0][item_name]" value="{{ old('items.0.item_name', 'Industrial Microprocessor Modules') }}" required placeholder="e.g. Server Racks"
                                        class="w-full px-2.5 py-1.5 bg-[#120b24] border border-[#33215c] rounded-lg text-xs text-white focus:outline-none focus:ring-1 focus:ring-pink-500">
                                </td>
                                <td class="py-2.5 px-3">
                                    <input type="number" name="items[0][quantity]" value="{{ old('items.0.quantity', 20) }}" min="1" required
                                        class="w-full px-2.5 py-1.5 bg-[#120b24] border border-[#33215c] rounded-lg text-xs text-white focus:outline-none focus:ring-1 focus:ring-pink-500">
                                </td>
                                <td class="py-2.5 px-3">
                                    <input type="number" step="0.01" name="items[0][weight]" value="{{ old('items.0.weight', 600) }}"
                                        class="item-weight w-full px-2.5 py-1.5 bg-[#120b24] border border-[#33215c] rounded-lg text-xs text-white focus:outline-none focus:ring-1 focus:ring-pink-500">
                                </td>
                                <td class="py-2.5 px-3">
                                    <input type="text" name="items[0][description]" value="{{ old('items.0.description', 'Cleanroom sealed packaging') }}" placeholder="Packaging, Hazmat notes..."
                                        class="w-full px-2.5 py-1.5 bg-[#120b24] border border-[#33215c] rounded-lg text-xs text-white focus:outline-none focus:ring-1 focus:ring-pink-500">
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    <button type="button" onclick="removeItemRow(this)" class="p-1 rounded text-slate-500 hover:text-rose-400" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Submit -->
            <div class="pt-4 border-t border-[#33215c] flex items-center justify-end space-x-3">
                <a href="{{ route('shipments.index') }}" class="px-5 py-2.5 rounded-xl bg-[#33215c] hover:bg-[#3d276e] text-slate-300 text-xs font-semibold">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pink-500 via-fuchsia-500 to-rose-600 hover:from-pink-400 hover:to-rose-500 text-white font-bold text-xs shadow-lg shadow-pink-600/20">
                    Register Shipment & Initialize Lifecycle
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let itemIndex = 1;

    function addItemRow() {
        const tbody = document.getElementById('itemsTableBody');
        const row = document.createElement('tr');
        row.className = 'item-row bg-[#1c1236]';
        row.innerHTML = `
            <td class="py-2.5 px-3">
                <input type="text" name="items[${itemIndex}][item_name]" required placeholder="Item Name"
                    class="w-full px-2.5 py-1.5 bg-[#120b24] border border-[#33215c] rounded-lg text-xs text-white focus:outline-none focus:ring-1 focus:ring-pink-500">
            </td>
            <td class="py-2.5 px-3">
                <input type="number" name="items[${itemIndex}][quantity]" value="1" min="1" required
                    class="w-full px-2.5 py-1.5 bg-[#120b24] border border-[#33215c] rounded-lg text-xs text-white focus:outline-none focus:ring-1 focus:ring-pink-500">
            </td>
            <td class="py-2.5 px-3">
                <input type="number" step="0.01" name="items[${itemIndex}][weight]" value="100"
                    class="item-weight w-full px-2.5 py-1.5 bg-[#120b24] border border-[#33215c] rounded-lg text-xs text-white focus:outline-none focus:ring-1 focus:ring-pink-500">
            </td>
            <td class="py-2.5 px-3">
                <input type="text" name="items[${itemIndex}][description]" placeholder="Description / Specs"
                    class="w-full px-2.5 py-1.5 bg-[#120b24] border border-[#33215c] rounded-lg text-xs text-white focus:outline-none focus:ring-1 focus:ring-pink-500">
            </td>
            <td class="py-2.5 px-3 text-right">
                <button type="button" onclick="removeItemRow(this)" class="p-1 rounded text-slate-500 hover:text-rose-400" title="Remove">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </td>
        `;
        tbody.appendChild(row);
        itemIndex++;
    }

    function removeItemRow(btn) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) {
            btn.closest('tr').remove();
        } else {
            alert('At least one manifest item must be provided.');
        }
    }
</script>
@endsection
