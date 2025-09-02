<?php

namespace App\Models\Users;

use App\Models\Appointment;
use App\Models\Companies\Company;
use App\Models\Voucher;
use App\Policies\Users\UserPolicy;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[UsePolicy(UserPolicy::class)]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->companies()->whereKey($tenant)->exists();
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->companies;
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_employees', 'user_id', 'company_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'user_id');
    }

    public function detail(): HasOne
    {
        return $this->hasOne(Detail::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function scheduledAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
