<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Traits\LogsActivity;
use Exception;

class UserController extends Controller
{
    use LogsActivity;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        // Filter by role
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }
        // Sort
        if ($request->filled('sort') && $request->sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $users = $query->paginate(10);

        return view('Users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'role'       => 'required|in:admin,tenant,landlord',
            'password'   => 'required|string|min:6',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'role'       => $request->role,
            'password'   => Hash::make($request->password),
        ]);

        $this->logActivity('create_user', "Created user: {$user->first_name} {$user->last_name} ({$user->email})");

        return redirect()->route('admin.users.index')->with('crud_success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'role'       => 'required|in:admin,tenant,landlord',
            'password'   => 'nullable|string|min:6',
            'avatar'     => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && file_exists(storage_path('app/public/avatars/' . $user->avatar))) {
                unlink(storage_path('app/public/avatars/' . $user->avatar));
            }

            $avatarFile = $request->file('avatar');
            $avatarName = time() . '.' . $avatarFile->getClientOriginalExtension();
            
            // Ensure the avatars directory exists
            $avatarPath = storage_path('app/public/avatars');
            if (!file_exists($avatarPath)) {
                mkdir($avatarPath, 0755, true);
            }
            
            // Try multiple methods to store the file
            $stored = false;
            
            // Method 1: Using Laravel's storeAs
            try {
                $avatarFile->storeAs('public/avatars', $avatarName);
                if (file_exists(storage_path('app/public/avatars/' . $avatarName))) {
                    $stored = true;
                }
            } catch (Exception $e) {
                // Continue to next method
            }
            
            // Method 2: Direct file move if Laravel method failed
            if (!$stored) {
                try {
                    $avatarFile->move(storage_path('app/public/avatars'), $avatarName);
                    if (file_exists(storage_path('app/public/avatars/' . $avatarName))) {
                        $stored = true;
                    }
                } catch (Exception $e) {
                    // Continue to next method
                }
            }
            
            // Method 3: Copy file contents if move failed
            if (!$stored) {
                try {
                    $fileContents = file_get_contents($avatarFile->getPathname());
                    file_put_contents(storage_path('app/public/avatars/' . $avatarName), $fileContents);
                    if (file_exists(storage_path('app/public/avatars/' . $avatarName))) {
                        $stored = true;
                    }
                } catch (Exception $e) {
                    // Log error or handle as needed
                }
            }
            
            if ($stored) {
                $user->avatar = $avatarName;
            } else {
                return back()->withErrors(['avatar' => 'Failed to upload avatar. Please try again.']);
            }
        }

        $user->first_name = $request->first_name;
        $user->last_name  = $request->last_name;
        $user->email      = $request->email;
        $user->role       = $request->role;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        $this->logActivity('update_user', "Updated user: {$user->first_name} {$user->last_name} ({$user->email})");

        return redirect()->route('admin.users.index')->with('crud_success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }
        $user = User::findOrFail($id);
        $userName = "{$user->first_name} {$user->last_name} ({$user->email})";
        $user->delete();

        $this->logActivity('delete_user', "Deleted user: $userName");

        return redirect()->route('admin.users.index')->with('crud_success', 'User deleted successfully.');
    }

    /**
     * Show the form for editing the authenticated user's profile.
     */
    public function editProfile()
    {
        $user = auth()->user();
        return view('Users.edit-profile', compact('user'));
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'current_password' => 'required_with:password|nullable|string',
            'password'   => 'nullable|string|min:6|confirmed',
            'avatar'     => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Verify current password if trying to change password
        if ($request->filled('password')) {
            // Check if current password is provided when trying to change password
            if (!$request->filled('current_password')) {
                return back()->withErrors(['current_password' => 'Current password is required when changing password.']);
            }
            
            // Verify current password is correct
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
            
            // Check if new password is the same as current password
            if (Hash::check($request->password, $user->password)) {
                return back()->withErrors(['password' => 'New password must be different from your current password.']);
            }
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && file_exists(storage_path('app/public/avatars/' . $user->avatar))) {
                unlink(storage_path('app/public/avatars/' . $user->avatar));
            }

            $avatarFile = $request->file('avatar');
            $avatarName = time() . '.' . $avatarFile->getClientOriginalExtension();
            
            // Ensure the avatars directory exists
            $avatarPath = storage_path('app/public/avatars');
            if (!file_exists($avatarPath)) {
                mkdir($avatarPath, 0755, true);
            }
            
            // Try multiple methods to store the file
            $stored = false;
            
            // Method 1: Using Laravel's storeAs
            try {
                $avatarFile->storeAs('public/avatars', $avatarName);
                if (file_exists(storage_path('app/public/avatars/' . $avatarName))) {
                    $stored = true;
                }
            } catch (Exception $e) {
                // Continue to next method
            }
            
            // Method 2: Direct file move if Laravel method failed
            if (!$stored) {
                try {
                    $avatarFile->move(storage_path('app/public/avatars'), $avatarName);
                    if (file_exists(storage_path('app/public/avatars/' . $avatarName))) {
                        $stored = true;
                    }
                } catch (Exception $e) {
                    // Continue to next method
                }
            }
            
            // Method 3: Copy file contents if move failed
            if (!$stored) {
                try {
                    $fileContents = file_get_contents($avatarFile->getPathname());
                    file_put_contents(storage_path('app/public/avatars/' . $avatarName), $fileContents);
                    if (file_exists(storage_path('app/public/avatars/' . $avatarName))) {
                        $stored = true;
                    }
                } catch (Exception $e) {
                    // Log error or handle as needed
                }
            }
            
            if ($stored) {
                $user->avatar = $avatarName;
            } else {
                return back()->withErrors(['avatar' => 'Failed to upload avatar. Please try again.']);
            }
        }

        // Update user information
        $user->first_name = $request->first_name;
        $user->last_name  = $request->last_name;
        $user->email      = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        $this->logActivity('update_profile', "Updated profile: {$user->first_name} {$user->last_name}");

        return redirect()->route('profile.edit')->with('crud_success', 'Profile updated successfully.');
    }
}
