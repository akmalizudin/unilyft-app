<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */

    use HasFactory, Notifiable, HasApiTokens, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'gender',
        'phoneNumber',
        'password',
        'role',
        'rating',
        'isVerified',
        'vehicle_model',
        'vehicle_year',
        'registration_number',
        'otp',
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

    protected $appends = ['avatar', 'driving_license', 'vehicle_photo'];

    public function carpools()
    {
        return $this->belongsToMany(Carpool::class, 'carpool_user', 'user_id', 'carpool_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'authorId');
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('user_avatar')->singleFile()
            ->useFallbackUrl(config('app.default.image.uri'))
            ->useFallbackPath(public_path(config('app.default.image.uri')))
            ->registerMediaConversions(function (Media $media) {
                $this
                    ->addMediaConversion('thumb')
                    ->width(100)
                    ->height(100);
            });

        $this->addMediaCollection('driving_license')->singleFile();
    }

    protected function getAvatarAttribute()
    {
        $media = $this->getFirstMedia('user_avatar');

        if ($media) {
            return [
                'uri' => $media->getFullUrl(),
                'name' => $media->file_name,
                'type' => $media->mime_type,
            ];
        }

        return [
            'uri' => config('app.default.image.uri'),
            'name' => config('app.default.image.name'),
            'type' => config('app.default.image.type'),
        ];
    }

    protected function getDrivingLicenseAttribute()
    {
        $media = $this->getFirstMedia('driving_license');

        if ($media) {
            return [
                'uri' => $media->getFullUrl(),
                'name' => $media->file_name,
                'type' => $media->mime_type,
            ];
        }

        return null;
    }

    protected function getVehiclePhotoAttribute()
    {
        $media = $this->getFirstMedia('vehicle_photo');

        if ($media) {
            return [
                'uri' => $media->getFullUrl(),
                'name' => $media->file_name,
                'type' => $media->mime_type,
            ];
        }

        return null;
    }

    public function triggeredNotifications()
    {
        return $this->hasMany(Notification::class, 'triggered_by_id');
    }

}
