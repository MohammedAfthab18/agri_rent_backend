<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FarmerProfile;
use App\Models\OwnerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle login request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'phone' => 'required|string|min:10|max:15',
                'password' => 'required|string|min:6',
            ]);

            // Find user by phone
            $user = User::with(['farmerProfile', 'ownerProfile'])->where('phone', $request->phone)->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number or password.',
                    'errors' => [
                        'phone' => ['The provided credentials are incorrect.'],
                    ]
                ], 401);
            }

            // Check if user is active
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support.',
                ], 403);
            }

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            // Get active profile data
            $activeProfile = null;
            if ($user->active_role === 'farmer' && $user->farmerProfile) {
                $activeProfile = [
                    'type' => 'farmer',
                    'profile' => $user->farmerProfile,
                    'is_complete' => $user->farmerProfile->isComplete(),
                ];
            } elseif ($user->active_role === 'owner' && $user->ownerProfile) {
                $activeProfile = [
                    'type' => 'owner',
                    'profile' => $user->ownerProfile,
                    'is_complete' => $user->ownerProfile->isComplete(),
                ];
            }

            // Return success response with user and token
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'phone' => $user->phone,
                        'name' => $user->name,
                        'primary_role' => $user->primary_role,
                        'active_role' => $user->active_role,
                        'has_farmer_profile' => $user->hasFarmerProfile(),
                        'has_owner_profile' => $user->hasOwnerProfile(),
                        'is_active' => $user->is_active,
                        'created_at' => $user->created_at->toIso8601String(),
                        'updated_at' => $user->updated_at->toIso8601String(),
                    ],
                    'active_profile' => $activeProfile,
                    'token' => $token,
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Switch user role
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function switchRole(Request $request)
    {
        try {
            $request->validate([
                'role' => 'required|in:farmer,owner',
            ]);

            $user = $request->user();
            $newRole = $request->role;

            // Check if user has profile for the role they want to switch to
            if ($newRole === 'farmer' && !$user->hasFarmerProfile()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You need to complete your farmer profile first.',
                    'requires_profile_setup' => true,
                ], 400);
            }

            if ($newRole === 'owner' && !$user->hasOwnerProfile()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You need to complete your owner profile first.',
                    'requires_profile_setup' => true,
                ], 400);
            }

            // Switch role
            if ($user->switchRole($newRole)) {
                $user->load(['farmerProfile', 'ownerProfile']);
                
                // Get active profile data
                $activeProfile = null;
                if ($newRole === 'farmer') {
                    $activeProfile = [
                        'type' => 'farmer',
                        'profile' => $user->farmerProfile,
                        'is_complete' => $user->farmerProfile->isComplete(),
                    ];
                } else {
                    $activeProfile = [
                        'type' => 'owner',
                        'profile' => $user->ownerProfile,
                        'is_complete' => $user->ownerProfile->isComplete(),
                    ];
                }

                return response()->json([
                    'success' => true,
                    'message' => "Successfully switched to {$newRole} role",
                    'data' => [
                        'active_role' => $user->active_role,
                        'active_profile' => $activeProfile,
                    ]
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to switch role. Please try again.',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while switching role.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Check if user can switch to a role
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkRoleAvailability(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'data' => [
                    'current_role' => $user->active_role,
                    'primary_role' => $user->primary_role,
                    'available_roles' => [
                        'farmer' => [
                            'available' => true,
                            'has_profile' => $user->hasFarmerProfile(),
                            'is_complete' => $user->farmerProfile ? $user->farmerProfile->isComplete() : false,
                        ],
                        'owner' => [
                            'available' => true,
                            'has_profile' => $user->hasOwnerProfile(),
                            'is_complete' => $user->ownerProfile ? $user->ownerProfile->isComplete() : false,
                        ],
                    ],
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check role availability.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Handle logout request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // Revoke the current user's token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during logout.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get current authenticated user with profiles
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        try {
            $user = $request->user()->load(['farmerProfile', 'ownerProfile']);

            // Get active profile data
            $activeProfile = null;
            if ($user->active_role === 'farmer' && $user->farmerProfile) {
                $activeProfile = [
                    'type' => 'farmer',
                    'profile' => $user->farmerProfile,
                    'is_complete' => $user->farmerProfile->isComplete(),
                ];
            } elseif ($user->active_role === 'owner' && $user->ownerProfile) {
                $activeProfile = [
                    'type' => 'owner',
                    'profile' => $user->ownerProfile,
                    'is_complete' => $user->ownerProfile->isComplete(),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'phone' => $user->phone,
                    'name' => $user->name,
                    'primary_role' => $user->primary_role,
                    'active_role' => $user->active_role,
                    'has_farmer_profile' => $user->hasFarmerProfile(),
                    'has_owner_profile' => $user->hasOwnerProfile(),
                    'is_active' => $user->is_active,
                    'active_profile' => $activeProfile,
                    'created_at' => $user->created_at->toIso8601String(),
                    'updated_at' => $user->updated_at->toIso8601String(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user data.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Check authentication status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAuth(Request $request)
    {
        try {
            if ($request->user()) {
                return response()->json([
                    'success' => true,
                    'authenticated' => true,
                    'user' => [
                        'id' => $request->user()->id,
                        'phone' => $request->user()->phone,
                        'name' => $request->user()->name,
                        'active_role' => $request->user()->active_role,
                    ]
                ], 200);
            }

            return response()->json([
                'success' => true,
                'authenticated' => false,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'authenticated' => false,
                'message' => 'Failed to check authentication status.',
            ], 500);
        }
    }
}