<?php

namespace App\Http\Controllers\Admin;

use App\Models\Report;
use App\Models\Response;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController
{
    public function index(Request $request)
    {
        // The dashboard stat cards link here with ?role=, so honour that filter.
        $roleFilter = $request->query('role');
        $roleFilter = is_string($roleFilter) && in_array($roleFilter, User::assignableRoles(), true)
            ? $roleFilter
            : null;

        $users = User::query()
            ->when($roleFilter !== null, fn ($query) => $query->where('role', $roleFilter))
            ->withCount(['reports', 'responses'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roleFilter' => $roleFilter,
        ]);
    }

    public function create()
    {
        return view('admin.users.create', [
            'roles' => User::assignableRoles(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::in(User::assignableRoles())],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ], $this->messages());

        // The User model's hashed cast hashes the plain-text password on save.
        User::create($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'user-created');
    }

    public function edit(User $user)
    {
        abort_if($user->isAdmin(), 403);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => User::assignableRoles(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->isAdmin(), 403);
        abort_if($user->is($request->user()), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
            'role' => ['required', 'string', Rule::in(User::assignableRoles())],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)],
        ], $this->messages());

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        // An empty password field means "keep the current password".
        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'user-updated');
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->isAdmin(), 403);
        abort_if($user->is($request->user()), 403);

        $reportCount = Report::query()->where('users_id', $user->getKey())->count();
        $responseCount = Response::query()->where('users_id', $user->getKey())->count();

        if ($reportCount > 0 || $responseCount > 0) {
            throw ValidationException::withMessages([
                'user' => $this->inUseMessage($reportCount, $responseCount),
            ]);
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'user-deleted');
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => 'Masukkan nama pengguna.',
            'name.max' => 'Nama pengguna maksimal 255 karakter.',
            'email.required' => 'Masukkan email pengguna.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah digunakan pengguna lain.',
            'role.required' => 'Pilih peran pengguna.',
            'role.in' => 'Peran yang dipilih tidak diizinkan.',
            'password.required' => 'Masukkan kata sandi awal.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ];
    }

    private function inUseMessage(int $reportCount, int $responseCount): string
    {
        $parts = [];

        if ($reportCount > 0) {
            $parts[] = $reportCount.' laporan';
        }

        if ($responseCount > 0) {
            $parts[] = $responseCount.' pesan diskusi';
        }

        return 'Pengguna ini masih memiliki '.implode(' dan ', $parts).' sehingga tidak dapat dihapus.';
    }
}
