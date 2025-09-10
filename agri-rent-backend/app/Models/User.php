<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone',
        'name',
        'password',
        'primary_role',
        'active_role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be used for authentication.
     *
     * @return string
     */
    public function username()
    {
        return 'phone';
    }

    /**
     * Get the farmer profile for the user.
     */
    public function farmerProfile()
    {
        return $this->hasOne(FarmerProfile::class);
    }

    /**
     * Get the owner profile for the user.
     */
    public function ownerProfile()
    {
        return $this->hasOne(OwnerProfile::class);
    }

    /**
     * Check if user has farmer profile
     */
    public function hasFarmerProfile()
    {
        return $this->farmerProfile()->exists();
    }

    /**
     * Check if user has owner profile
     */
    public function hasOwnerProfile()
    {
        return $this->ownerProfile()->exists();
    }

    /**
     * Get active profile based on active_role
     */
    public function getActiveProfile()
    {
        if ($this->active_role === 'farmer') {
            return $this->farmerProfile;
        } else {
            return $this->ownerProfile;
        }
    }

    /**
     * Switch user role
     */
    public function switchRole($role)
    {
        if (!in_array($role, ['farmer', 'owner'])) {
            return false;
        }

        // Check if user has the profile for the role they want to switch to
        if ($role === 'farmer' && !$this->hasFarmerProfile()) {
            return false;
        }

        if ($role === 'owner' && !$this->hasOwnerProfile()) {
            return false;
        }

        $this->active_role = $role;
        return $this->save();
    }

    /**
     * Check if user can switch to a specific role
     */
    public function canSwitchTo($role)
    {
        if ($role === 'farmer') {
            return $this->hasFarmerProfile();
        } elseif ($role === 'owner') {
            return $this->hasOwnerProfile();
        }
        return false;
    }
}