<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FarmerProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'farm_name',
        'farm_location',
        'farm_size',
        'farm_type',
        'years_of_experience',
        'crop_types',
        'livestock_types',
        'village',
        'taluk',
        'district',
        'state',
        'pincode',
        'is_verified',
        'verified_at',
        'additional_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'crop_types' => 'array',
        'livestock_types' => 'array',
        'farm_size' => 'decimal:2',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the farmer profile.
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
            $this->village,
            $this->taluk,
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
            'farm_location',
            'farm_size',
            'farm_type',
            'years_of_experience',
            'village',
            'taluk',
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
}