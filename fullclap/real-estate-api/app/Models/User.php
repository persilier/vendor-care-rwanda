<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements JWTSubject, HasMedia
{
    use HasUuid, Notifiable, InteractsWithMedia;

    protected $fillable = [
        'name',
        'email',
        'password',
        'photo',
        'status',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'string',
        'two_factor_confirmed_at' => 'datetime'
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_photo')
            ->singleFile()
            ->useFallbackUrl('/images/default-profile.jpg')
            ->useFallbackPath(public_path('/images/default-profile.jpg'));
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->nonQueued();
    }

    public function getProfilePhotoUrlAttribute()
    {
        return $this->getFirstMediaUrl('profile_photo', 'medium') ?: $this->getFirstMediaUrl('profile_photo');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    public function enableTwoFactorAuthentication(string $secret): void
    {
        $this->two_factor_secret = $secret;
        $this->two_factor_confirmed_at = now();
        $this->save();
    }

    public function disableTwoFactorAuthentication(): void
    {
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
        $this->two_factor_confirmed_at = null;
        $this->save();
    }

    public function setRecoveryCodes(array $codes): void
    {
        $this->two_factor_recovery_codes = json_encode($codes);
        $this->save();
    }
}