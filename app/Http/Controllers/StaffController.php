<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $project = $request->user()->currentProject();

        $staff = User::where('project_id', $project->id)
            ->whereIn('role', [User::ROLE_MANAGER, User::ROLE_CASHIER])
            ->orderBy('name')
            ->get();

        return view('staff.index', [
            'project' => $project,
            'staff' => $staff,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = $request->user()->currentProject();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:'.User::ROLE_MANAGER.','.User::ROLE_CASHIER],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'project_id' => $project->id,
            'email_verified_at' => now(),
        ]);

        return back()->with('success', 'Akaun staff berjaya dibuat.');
    }

    public function toggle(Request $request, User $staffMember): RedirectResponse
    {
        abort_unless($staffMember->project_id === $request->user()->currentProject()?->id, 403);

        $staffMember->update(['is_active' => ! $staffMember->is_active]);

        return back()->with('success', 'Status staff dikemaskini.');
    }

    public function destroy(Request $request, User $staffMember): RedirectResponse
    {
        abort_unless($staffMember->project_id === $request->user()->currentProject()?->id, 403);

        $staffMember->delete();

        return back()->with('success', 'Akaun staff dipadam.');
    }
}
