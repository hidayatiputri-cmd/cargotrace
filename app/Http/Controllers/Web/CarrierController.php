<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CarrierController extends Controller
{
    public function index(): View
    {
        Gate::authorize('create', Carrier::class);

        $carriers = Carrier::withCount('shipments')->latest()->paginate(15);

        return view('admin.carriers.index', compact('carriers'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Carrier::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'unique:carriers,code'],
            'contact_info' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        Carrier::create($validated);

        return redirect()->route('admin.carriers.index')
            ->with('success', 'Carrier added successfully.');
    }

    public function update(Request $request, Carrier $carrier): RedirectResponse
    {
        Gate::authorize('update', $carrier);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'unique:carriers,code,' . $carrier->id],
            'contact_info' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $carrier->update($validated);

        return redirect()->route('admin.carriers.index')
            ->with('success', 'Carrier updated successfully.');
    }

    public function destroy(Carrier $carrier): RedirectResponse
    {
        Gate::authorize('delete', $carrier);

        $carrier->delete();

        return redirect()->route('admin.carriers.index')
            ->with('success', 'Carrier deleted.');
    }
}
