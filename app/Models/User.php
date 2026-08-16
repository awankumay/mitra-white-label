<?php

namespace App\Models;

use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Core\Security\Models\SecurityEvent;
use Core\Support\Concerns\UsesUuid;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasPanelShield;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;
    use UsesUuid;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(OrganizationalUnit::class);
    }

    public function primaryOrganizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'primary_organizational_unit_id');
    }

    public function securityEvents(): HasMany
    {
        return $this->hasMany(SecurityEvent::class, 'user_id');
    }
}
