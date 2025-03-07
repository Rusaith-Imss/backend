<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    // Middleware to restrict access based on roles
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin|superadmin')->only(['create', 'updateRole', 'destroy']);
    }

    // Create a new user with role-based access
    public function create(Request $request)
    {
        Log::info('Create user request received', $request->all());

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:user,admin,staff,superadmin,storekeeper',
            'status' => 'required|string|in:active,suspended',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate photo
        ]);

        try {
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('user_photos', 'public');
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status' => $request->status,
                'photo' => $photoPath,
            ]);

            Log::info('User created successfully', ['user_id' => $user->id]);

            return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
        } catch (\Exception $e) {
            Log::error('User creation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'User creation failed', 'message' => $e->getMessage()], 500);
        }
    }

    // Get all users with filters
    public function index(Request $request)
    {
        try {
            $query = User::query();

            // Apply filters
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Paginate results
            $users = $query->paginate(10); // 10 users per page

            return response()->json(['users' => $users], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve users', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to retrieve users', 'message' => $e->getMessage()], 500);
        }
    }

    // Update user role
    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|in:user,admin,staff,superadmin,storekeeper',
        ]);

        try {
            $user->role = $request->role;
            $user->save();

            return response()->json(['message' => 'User role updated successfully', 'user' => $user], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update user role', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update user role', 'message' => $e->getMessage()], 500);
        }
    }

    // Update user status (Active/Suspended)
    public function updateStatus(Request $request, User $user)
    {
        $request->validate([
            'status' => 'required|string|in:active,suspended',
        ]);

        try {
            $user->status = $request->status;
            $user->save();

            return response()->json(['message' => 'User status updated successfully', 'user' => $user], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update user status', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update user status', 'message' => $e->getMessage()], 500);
        }
    }

    // Delete a user (Only Admin & Superadmin)
    public function destroy(Request $request, User $user)
    {
        if (!$request->user()->hasRole('admin') && !$request->user()->hasRole('superadmin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $user->delete();
            return response()->json(['message' => 'User deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete user', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete user', 'message' => $e->getMessage()], 500);
        }
    }

    // Change Password (For Logged-in User)
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $user = Auth::user();

            if (!Hash::check($request->current_password, $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'Current password is incorrect']);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json(['message' => 'Password updated successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update password', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update password', 'message' => $e->getMessage()], 500);
        }
    }

    // Update User Profile (For Logged-in User)
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'string|max:255|nullable',
            'email' => 'string|email|max:255|unique:users,email,' . Auth::id(),
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate photo
        ]);

        try {
            $user = Auth::user();

            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            if ($request->hasFile('photo')) {
                if ($user->photo) {
                    Storage::disk('public')->delete($user->photo);
                }
                $user->photo = $request->file('photo')->store('user_photos', 'public');
            }

            $user->save();

            return response()->json(['message' => 'Profile updated successfully', 'user' => $user], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update profile', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update profile', 'message' => $e->getMessage()], 500);
        }
    }

    // Get User Activity Log (Future Implementation)
    public function activityLog(User $user)
    {
        // Placeholder for activity tracking logic
        return response()->json(['message' => 'Activity log feature coming soon'], 200);
    }

    // Enable Two-Factor Authentication (2FA) - Placeholder
    public function enable2FA(User $user)
    {
        // Placeholder for enabling 2FA
        return response()->json(['message' => '2FA feature coming soon'], 200);
    }
}