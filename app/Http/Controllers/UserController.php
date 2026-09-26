<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('role', 'asc')->orderBy('name', 'asc')->paginate(10);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        // Validasi kondisional: jika role owner, email & password wajib diisi
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'role'     => ['required', 'in:owner,worker'],
            'email'    => ['nullable', 'required_if:role,owner', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'required_if:role,owner', 'min:6'],
        ], [
            'email.required_if'    => 'Email wajib diisi untuk akun Owner.',
            'password.required_if' => 'Kata sandi wajib diisi untuk akun Owner.',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            $validated['password'] = null;
        }

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'Pengguna baru berhasil ditambahkan!');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'role'     => ['required', 'in:owner,worker'],
            'email'    => ['nullable', 'required_if:role,owner', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'min:6'],
        ]);

        // Perbarui password hanya jika diisi
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui!');
    }

    public function destroy(User $user)
    {
        // Mencegah owner menghapus akunnya sendiri saat sedang login
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri saat sedang login.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus!');
    }
}