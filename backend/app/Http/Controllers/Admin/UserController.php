<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->role, fn ($q) => $q->where('role', $request->role))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                  ->orWhere('email', 'ilike', "%{$request->search}%");
            }))
            ->withCount(['messages', 'campaigns', 'contacts'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users',
            'password'      => 'required|string|min:8',
            'role'          => ['required', Rule::in(['admin', 'client', 'developer'])],
            'monthly_limit' => 'nullable|integer|min:0',
        ]);

        $user = User::create([
            ...$data,
            'password'       => bcrypt($data['password']),
            'is_active'      => true,
            'quota_reset_at' => now()->addMonth()->startOfMonth(),
        ]);

        return response()->json($user, 201);
    }

    public function show(int $id)
    {
        $user = User::withCount(['messages', 'campaigns', 'contacts'])
            ->with('apiKeys')
            ->findOrFail($id);

        return response()->json($user);
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'email'         => ['sometimes', 'email', Rule::unique('users')->ignore($id)],
            'role'          => ['sometimes', Rule::in(['admin', 'client', 'developer'])],
            'is_active'     => 'sometimes|boolean',
            'monthly_limit' => 'sometimes|nullable|integer|min:0',
            'password'      => 'sometimes|string|min:8',
        ]);

        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'No puedes eliminar tu propia cuenta.'], 422);
        }

        $user->delete();
        return response()->json(['deleted' => true]);
    }

    public function toggleActive(int $id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => ! $user->is_active]);
        return response()->json(['is_active' => $user->is_active]);
    }
}
