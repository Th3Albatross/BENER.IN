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

        $search = trim((string) $request->query('q', ''));
        $search = mb_substr($search, 0, 255);

        $baseQuery = User::query()
            ->when($roleFilter !== null, fn ($query) => $query->where('role', $roleFilter))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%")
                        ->orWhere('classroom', 'like', "%{$search}%");
                });
            });

        // Recompute the role tabs under the active search so the counts match
        // what the table is about to show (skill §11: a count names its filter).
        $roleTotals = (clone $baseQuery)
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $users = (clone $baseQuery)
            ->withCount(['reports', 'responses'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $tabBase = $search !== '' ? ['q' => $search] : [];

        return view('admin.users.index', [
            'users' => $users,
            'roleFilter' => $roleFilter,
            'search' => $search,
            'roleTabs' => [
                [
                    'value' => '',
                    'label' => 'Semua',
                    'count' => (int) $roleTotals->sum(),
                    'href' => route('admin.users.index', $tabBase),
                    'active' => $roleFilter === null,
                ],
                [
                    'value' => 'siswa',
                    'label' => 'Siswa',
                    'count' => (int) $roleTotals->get('siswa', 0),
                    'href' => route('admin.users.index', $tabBase + ['role' => 'siswa']),
                    'active' => $roleFilter === 'siswa',
                ],
                [
                    'value' => 'petugas',
                    'label' => 'Petugas',
                    'count' => (int) $roleTotals->get('petugas', 0),
                    'href' => route('admin.users.index', $tabBase + ['role' => 'petugas']),
                    'active' => $roleFilter === 'petugas',
                ],
            ],
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
            'role' => ['required', 'string', Rule::in(User::assignableRoles())],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ] + $this->identityRules($request->input('role')), $this->messages());

        // Students carry a NIS and classroom; staff only ever use email.
        $validated = $this->normalizeIdentity($validated);

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
            'role' => ['required', 'string', Rule::in(User::assignableRoles())],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)],
        ] + $this->identityRules($request->input('role'), $user), $this->messages());

        $validated = $this->normalizeIdentity($validated);

        $user->fill([
            'name' => $validated['name'],
            'nis' => $validated['nis'] ?? null,
            'classroom' => $validated['classroom'] ?? null,
            'email' => $validated['email'] ?? null,
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

    /**
     * Identity rules depend on the chosen role: students are identified by NIS
     * and classroom, while petugas/admin are identified by email.
     *
     * @return array<string, array<int, mixed>>
     */
    private function identityRules(mixed $role, ?User $user = null): array
    {
        $isStudent = $role === 'siswa';

        return [
            'nis' => [
                Rule::requiredIf($isStudent),
                'nullable',
                'string',
                'digits_between:4,20',
                Rule::unique('users', 'nis')->ignore($user?->getKey()),
            ],
            'classroom' => [
                Rule::requiredIf($isStudent),
                'nullable',
                'string',
                'max:20',
            ],
            'email' => [
                Rule::requiredIf(! $isStudent),
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->getKey()),
            ],
        ];
    }

    /**
     * Keep the two identity flavours apart: a petugas/admin never stores a NIS
     * or classroom, and a blank email on a student becomes NULL rather than ''.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeIdentity(array $validated): array
    {
        $isStudent = ($validated['role'] ?? null) === 'siswa';

        $validated['nis'] = $isStudent ? ($validated['nis'] ?? null) : null;
        $validated['classroom'] = $isStudent ? ($validated['classroom'] ?? null) : null;
        $validated['email'] = ! empty($validated['email']) ? $validated['email'] : null;

        return $validated;
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
            'nis.required' => 'Masukkan NIS siswa.',
            'nis.digits_between' => 'NIS harus berupa 4 sampai 20 angka.',
            'nis.unique' => 'NIS ini sudah digunakan siswa lain.',
            'classroom.required' => 'Masukkan kelas siswa.',
            'classroom.max' => 'Kelas maksimal 20 karakter.',
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
