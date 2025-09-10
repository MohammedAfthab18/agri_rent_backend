<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'business_name',
        'business_type',
        'gst_number',
        'years_in_business',
        'total_equipment_count',
        'equipment_types',
        'service_districts',
        'max_delivery_distance',
        'address_line_1',
        'address_line_2',
        'city',
        'district',
        'state',
        'pincode',
        'bank_name',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'is_verified',
        'verified_at',
        'provides_operator',
        'provides_delivery',
        'terms_and_conditions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'equipment_types' => 'array',
        'service_districts' => 'array',
        'max_delivery_distance' => 'decimal:2',
        'total_equipment_count' => 'integer',
        'is_verified' => 'boolean',
        'provides_operator' => 'boolean',
        'provides_delivery' => 'boolean',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'account_number',
        'ifsc_code',
    ];

    /**
     * Get the user that owns the owner profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute()
    {
        return implode(', ', array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->district,
            $this->state,
            $this->pincode
        ]));
    }

    /**
     * Check if profile is complete
     */
    public function isComplete()
    {
        $requiredFields = [
            'business_type',
            'years_in_business',
            'service_districts',
            'max_delivery_distance',
            'address_line_1',
            'city',
            'district',
            'pincode'
        ];

        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if bank details are complete
     */
    public function hasBankDetails()
    {
        return !empty($this->bank_name) && 
               !empty($this->account_holder_name) && 
               !empty($this->account_number) && 
               !empty($this->ifsc_code);
    }
}