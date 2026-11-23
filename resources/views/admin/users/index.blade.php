@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-white">Users & Role Access Management</h1>
            <p class="text-xs text-slate-400">Platform administrators, logistics operators, and customer consignee accounts</p>
        </div>
        <button type="button" onclick="document.getElementById('userModal').classList.remove('hidden')"
            class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg shadow-cyan-600/25 transition">
            + Create User
        </button>
    </div>

    <div class="bg-slate-900/90 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Name</th>
                    <th class="py-3 px-4">Email</th>
                    <th class="py-3 px-4">Role</th>
                    <th class="py-3 px-4">Registered Shipments</th>
                    <th class="py-3 px-4">Created Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @foreach($users as $u)
                    <tr class="hover:bg-slate-800/40">
                        <td class="py-3.5 px-4 font-bold text-white">{{ $u->name }}</td>
                        <td class="py-3.5 px-4 text-slate-300 font-mono">{{ $u->email }}</td>
                        <td class="py-3.5 px-4">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] uppercase font-bold
                                @if($u->role === 'admin') bg-purple-950 text-purple-300 border border-purple-800
                                @elseif($u->role === 'operator') bg-amber-950 text-amber-300 border border-amber-800
                                @else bg-cyan-950 text-cyan-300 border border-cyan-800 @endif">
                                {{ $u->role }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 font-bold text-slate-200">{{ $u->shipments_count }}</td>
                        <td class="py-3.5 px-4 text-slate-400">{{ $u->created_at->format('M d, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if($users->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-950">{{ $users->links() }}</div>
        @endif
    </div>
</div>

<!-- Modal: Add User -->
<div id="userModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
        <h3 class="text-base font-bold text-white mb-4">Create New Account</h3>
        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Full Name / Org</label>
                <input type="text" name="name" required placeholder="e.g. Pacific Logistics Desk" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-1 focus:ring-cyan-500">
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Email Address</label>
                <input type="email" name="email" required placeholder="desk@pacific.com" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-1 focus:ring-cyan-500">
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-1 focus:ring-cyan-500">
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Role</label>
                <select name="role" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white focus:ring-1 focus:ring-cyan-500">
                    <option value="customer">Customer</option>
                    <option value="operator">Logistics Operator</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="pt-3 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('userModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-800 text-xs text-slate-300">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-cyan-600 text-white font-bold text-xs">Create User</button>
            </div>
        </form>
    </div>
</div>
@endsection
