<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', ['users' => User::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'role' => ['required', Rule::in(['administrator', 'coach', 'inventory_manager', 'viewer'])],
        ]);
        $data['email'] = strtolower($data['email']);
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        $this->log($request, 'user.created', 'Created '.$user->role.' account '.$user->email);

        return back()->with('success', 'User account created.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'You cannot change your own role.');
        $data = $request->validate(['role' => ['required', Rule::in(['administrator', 'coach', 'inventory_manager', 'viewer'])]]);
        abort_if(
            $user->role === 'administrator'
                && $data['role'] !== 'administrator'
                && User::where('role', 'administrator')->count() <= 1,
            422,
            'The last administrator account cannot be demoted.'
        );
        $user->update($data);
        $this->log($request, 'user.role_updated', 'Updated role for '.$user->email);

        return back()->with('success', 'User role updated.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);
        $this->log($request, 'user.password_reset', 'Reset password for '.$user->email);

        return back()->with('success', 'Password reset. The new password was not displayed.');
    }

    private function log(Request $request, string $action, string $description): void
    {
        ActivityLog::create(['user_id' => $request->user()->id, 'action' => $action, 'description' => $description]);
    }
}
