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
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    /**
     * Handle registration request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'phone' => 'required|string|min:10|max:15|unique:users,phone',
                'name' => 'required|string|min:2|max:100',
                'password' => 'required|string|min:6|confirmed',
                'primary_role' => 'required|in:farmer,owner',
                
                // Common profile fields
                'district' => 'required|string|max:100',
                'state' => 'string|max:100',
                'pincode' => 'required|string|size:6',
                
                // Farmer-specific fields (conditional validation)
                'farm_location' => 'required_if:primary_role,farmer|string|max:255',
                'farm_size' => 'required_if:primary_role,farmer|numeric|min:0.1|max:10000',
                'farm_type' => 'required_if:primary_role,farmer|in:crop,livestock,mixed,organic,other',
                'years_of_experience' => 'required_if:primary_role,farmer|integer|min:0|max:100',
                'village' => 'required_if:primary_role,farmer|string|max:100',
                'taluk' => 'required_if:primary_role,farmer|string|max:100',
                'crop_types' => 'nullable|array',
                'livestock_types' => 'nullable|array',
                'farm_name' => 'nullable|string|max:255',
                'additional_notes' => 'nullable|string|max:1000',
                
                // Owner-specific fields (conditional validation)
                'business_type' => 'required_if:primary_role,owner|in:individual,company,partnership',
                'years_in_business' => 'required_if:primary_role,owner|integer|min:0|max:100',
                'service_districts' => 'required_if:primary_role,owner|array|min:1',
                'max_delivery_distance' => 'required_if:primary_role,owner|numeric|min:1|max:1000',
                'address_line_1' => 'required_if:primary_role,owner|string|max:255',
                'address_line_2' => 'nullable|string|max:255',
                'city' => 'required_if:primary_role,owner|string|max:100',
                'business_name' => 'nullable|string|max:255',
                'gst_number' => 'nullable|string|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
                'equipment_types' => 'nullable|array',
                'provides_operator' => 'boolean',
                'provides_delivery' => 'boolean',
                'terms_and_conditions' => 'nullable|string|max:2000',
            ]);

            // Set default state if not provided
            if (!isset($validatedData['state'])) {
                $validatedData['state'] = 'Tamil Nadu';
            }

            DB::beginTransaction();

            // Create user
            $user = User::create([
                'phone' => $validatedData['phone'],
                'name' => $validatedData['name'],
                'password' => Hash::make($validatedData['password']),
                'primary_role' => $validatedData['primary_role'],
                'active_role' => $validatedData['primary_role'], // Set active role same as primary initially
                'is_active' => true,
            ]);

            // Create profile based on role
            if ($validatedData['primary_role'] === 'farmer') {
                $this->createFarmerProfile($user, $validatedData);
            } else {
                $this->createOwnerProfile($user, $validatedData);
            }

            DB::commit();

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            // Load profiles for response
            $user->load(['farmerProfile', 'ownerProfile']);

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

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
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
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Create farmer profile
     *
     * @param User $user
     * @param array $data
     * @return FarmerProfile
     */
    private function createFarmerProfile(User $user, array $data)
    {
        return FarmerProfile::create([
            'user_id' => $user->id,
            'farm_name' => $data['farm_name'] ?? null,
            'farm_location' => $data['farm_location'],
            'farm_size' => $data['farm_size'],
            'farm_type' => $data['farm_type'],
            'years_of_experience' => $data['years_of_experience'],
            'crop_types' => $data['crop_types'] ?? null,
            'livestock_types' => $data['livestock_types'] ?? null,
            'village' => $data['village'],
            'taluk' => $data['taluk'],
            'district' => $data['district'],
            'state' => $data['state'],
            'pincode' => $data['pincode'],
            'additional_notes' => $data['additional_notes'] ?? null,
            'is_verified' => false,
        ]);
    }

    /**
     * Create owner profile
     *
     * @param User $user
     * @param array $data
     * @return OwnerProfile
     */
    private function createOwnerProfile(User $user, array $data)
    {
        return OwnerProfile::create([
            'user_id' => $user->id,
            'business_name' => $data['business_name'] ?? null,
            'business_type' => $data['business_type'],
            'gst_number' => $data['gst_number'] ?? null,
            'years_in_business' => $data['years_in_business'],
            'total_equipment_count' => 0, // Default, will be updated when equipment is added
            'equipment_types' => $data['equipment_types'] ?? null,
            'service_districts' => $data['service_districts'],
            'max_delivery_distance' => $data['max_delivery_distance'],
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'] ?? null,
            'city' => $data['city'],
            'district' => $data['district'],
            'state' => $data['state'],
            'pincode' => $data['pincode'],
            'provides_operator' => $data['provides_operator'] ?? false,
            'provides_delivery' => $data['provides_delivery'] ?? true,
            'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
            'is_verified' => false,
        ]);
    }

    /**
     * Check if phone number is available
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPhoneAvailability(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string|min:10|max:15',
            ]);

            $exists = User::where('phone', $request->phone)->exists();

            return response()->json([
                'success' => true,
                'available' => !$exists,
                'message' => $exists ? 'Phone number already registered' : 'Phone number is available',
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
                'message' => 'Failed to check phone availability.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get registration form configuration
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRegistrationConfig()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'farm_types' => [
                        'crop' => 'Crop Farming',
                        'livestock' => 'Livestock',
                        'mixed' => 'Mixed Farming',
                        'organic' => 'Organic Farming',
                        'other' => 'Other',
                    ],
                    'business_types' => [
                        'individual' => 'Individual',
                        'company' => 'Company',
                        'partnership' => 'Partnership',
                    ],
                    'common_crop_types' => [
                        'rice', 'wheat', 'sugarcane', 'cotton', 'groundnut', 
                        'coconut', 'banana', 'mango', 'tomato', 'onion',
                        'potato', 'brinjal', 'okra', 'chilli', 'turmeric'
                    ],
                    'common_livestock_types' => [
                        'cattle', 'buffalo', 'goat', 'sheep', 'chicken', 
                        'duck', 'fish', 'pig', 'horse'
                    ],
                    'common_equipment_types' => [
                        'tractor', 'harvester', 'plough', 'cultivator', 
                        'seed_drill', 'sprayer', 'thresher', 'rotavator',
                        'disc_harrow', 'power_tiller'
                    ],
                    'tamil_nadu_districts' => [
                        'Ariyalur', 'Chengalpattu', 'Chennai', 'Coimbatore', 
                        'Cuddalore', 'Dharmapuri', 'Dindigul', 'Erode', 
                        'Kallakurichi', 'Kanchipuram', 'Kanyakumari', 'Karur',
                        'Krishnagiri', 'Madurai', 'Mayiladuthurai', 'Nagapattinam',
                        'Namakkal', 'Nilgiris', 'Perambalur', 'Pudukkottai',
                        'Ramanathapuram', 'Ranipet', 'Salem', 'Sivaganga',
                        'Tenkasi', 'Thanjavur', 'Theni', 'Thoothukudi',
                        'Tiruchirappalli', 'Tirunelveli', 'Tirupathur', 
                        'Tiruppur', 'Tiruvallur', 'Tiruvannamalai', 'Tiruvarur',
                        'Vellore', 'Viluppuram', 'Virudhunagar'
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get registration configuration.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}