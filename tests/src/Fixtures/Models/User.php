<?php

declare(strict_types=1);

namespace Capell\Tests\Fixtures\Models;

use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Capell\Core\Database\Factories\UserFactory;
use Capell\Core\Models\Concerns\InteractsWithMedia;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Model implements AuthenticatableContract, AuthorizableContract, FilamentUser, HasMedia
{
    use Authenticatable;
    use AuthenticationLoggable;
    use Authorizable;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasPanelShield;
    use HasRoles;
    use InteractsWithMedia;
    use LogsActivity;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'bio',
        'profile_image_id',
        'avatar',
    ];

    /**
     * The guard name for the model.
     *
     * @var array
     */
    protected string $guard_name = 'web';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static string $factory = UserFactory::class;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user')
            ->logAll()
            ->logExcept([
                'email_verified_at',
                'password',
                'remember_token',
                'updated_at',
                'created_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
