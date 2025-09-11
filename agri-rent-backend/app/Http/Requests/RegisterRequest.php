<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // User basic information
            'phone' => 'required|string|min:10|max:15|unique:users,phone',
            'name' => 'required|string|min:2|max:100',
            'password' => 'required|string|min:6|confirmed',
            'primary_role' => 'required|in:farmer,owner',
            
            // Common location fields
            'district' => 'required|string|max:100',
            'state' => 'string|max:100',
            'pincode' => 'required|string|size:6|regex:/^[0-9]{6}$/',
            
            // Farmer-specific fields
            'farm_location' => 'required_if:primary_role,farmer|string|max:255',
            'farm_size' => 'required_if:primary_role,farmer|numeric|min:0.1|max:10000',
            'farm_type' => 'required_if:primary_role,farmer|in:crop,livestock,mixed,organic,other',
            'years_of_experience' => 'required_if:primary_role,farmer|integer|min:0|max:100',
            'village' => 'required_if:primary_role,farmer|string|max:100',
            'taluk' => 'required_if:primary_role,farmer|string|max:100',
            'crop_types' => 'nullable|array',
            'crop_types.*' => 'string|max:50',
            'livestock_types' => 'nullable|array',
            'livestock_types.*' => 'string|max:50',
            'farm_name' => 'nullable|string|max:255',
            'additional_notes' => 'nullable|string|max:1000',
            
            // Owner-specific fields
            'business_type' => 'required_if:primary_role,owner|in:individual,company,partnership',
            'years_in_business' => 'required_if:primary_role,owner|integer|min:0|max:100',
            'service_districts' => 'required_if:primary_role,owner|array|min:1',
            'service_districts.*' => 'string|max:100',
            'max_delivery_distance' => 'required_if:primary_role,owner|numeric|min:1|max:1000',
            'address_line_1' => 'required_if:primary_role,owner|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required_if:primary_role,owner|string|max:100',
            'business_name' => 'nullable|string|max:255',
            'gst_number' => [
                'nullable',
                'string',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'
            ],
            'equipment_types' => 'nullable|array',
            'equipment_types.*' => 'string|max:50',
            'provides_operator' => 'boolean',
            'provides_delivery' => 'boolean',
            'terms_and_conditions' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.unique' => 'This phone number is already registered.',
            'phone.min' => 'Phone number must be at least 10 digits.',
            'phone.max' => 'Phone number cannot exceed 15 digits.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'primary_role.in' => 'Please select either farmer or owner as your role.',
            'pincode.regex' => 'Pincode must be exactly 6 digits.',
            'gst_number.regex' => 'Please enter a valid GST number.',
            'farm_size.min' => 'Farm size must be at least 0.1 acres.',
            'max_delivery_distance.min' => 'Delivery distance must be at least 1 km.',
            'service_districts.min' => 'Please select at least one service district.',
            'years_of_experience.max' => 'Years of experience cannot exceed 100.',
            'years_in_business.max' => 'Years in business cannot exceed 100.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phone' => 'phone number',
            'primary_role' => 'role',
            'years_of_experience' => 'years of experience',
            'years_in_business' => 'years in business',
            'farm_size' => 'farm size',
            'max_delivery_distance' => 'maximum delivery distance',
            'service_districts' => 'service districts',
            'gst_number' => 'GST number',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Set default state if not provided
        if (!$this->has('state') || empty($this->state)) {
            $this->merge(['state' => 'Tamil Nadu']);
        }

        // Convert boolean strings to actual booleans
        if ($this->has('provides_operator')) {
            $this->merge(['provides_operator' => filter_var($this->provides_operator, FILTER_VALIDATE_BOOLEAN)]);
        }

        if ($this->has('provides_delivery')) {
            $this->merge(['provides_delivery' => filter_var($this->provides_delivery, FILTER_VALIDATE_BOOLEAN)]);
        }

        // Clean phone number (remove spaces, dashes, etc.)
        if ($this->has('phone')) {
            $cleanPhone = preg_replace('/[^0-9+]/', '', $this->phone);
            $this->merge(['phone' => $cleanPhone]);
        }

        // Clean pincode
        if ($this->has('pincode')) {
            $cleanPincode = preg_replace('/[^0-9]/', '', $this->pincode);
            $this->merge(['pincode' => $cleanPincode]);
        }
    }
}