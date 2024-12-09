<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'otp_status',
        'otp_attempts',
        'otp',
        'login_attempts',
        'created_at',
        'updated_at',
        'ban_until',
        'email_verified_at',
        'pivot'
    ];

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'country',
        'created_by',
        'updated_by',
        'profile_picture',
        'phone_number',
        'email_verified_at',
        'remember_token',
        'is_verified',
        'account_status',
        'user_role',
        'is_sdm_user',
        'otp_status',
        'otp_attempts',
        'otp',
        'login_attempts',
        'ban_until',
        'created_at',
        'updated_at'
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
    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function media()
    {
        return $this->morphOne(Media::class, 'mediaable');
    }
}
