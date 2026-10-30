<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\BusyCompanies;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TelecallerController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|alpha_dash|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:4|max:255',
            'branch' => 'nullable|string|max:255',
            'region' => ['nullable', 'string', Rule::in(array_keys(BusyCompanies::all()))],
            'city' => 'nullable|string|max:255',
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'telecaller',
            'initials' => crm_initials($validated['name']),
            'branch' => $validated['branch'] ?: 'All',
            'region' => $validated['region'] ?: null,
            'city' => $validated['city'] ?: null,
        ]);

        return redirect()
            ->route('admin.users')
            ->with('success', $validated['name'].' can now sign in to the mobile app as '.$validated['username'].'.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($user->role === 'telecaller', 404);

        // The password used to be the only thing this accepted, so a
        // telecaller's scope could never be changed after creation.
        $validated = $request->validate([
            'password' => 'nullable|string|min:4|max:255',
            'region' => ['nullable', 'string', Rule::in(array_keys(BusyCompanies::all()))],
            'city' => 'nullable|string|max:255',
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        // A blank region or city clears the scope, which is how an account is
        // put back to seeing every company.
        $user->region = $validated['region'] ?: null;
        $user->city = $validated['city'] ?: null;
        $user->save();

        return redirect()
            ->route('admin.users')
            ->with('success', $user->name.' updated.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($user->role === 'telecaller', 404);

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.users')
            ->with('success', $name.' was removed. Their past calls stay on the accounts.');
    }
}
