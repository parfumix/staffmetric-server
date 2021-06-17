<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;

class User extends Authenticatable {
    
    use HasFactory, Notifiable, HasApiTokens;

    use Sluggable;

    use SluggableScopeHelpers;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sluggable(): array {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }

     /**
     * Get user devices
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function devices() {
        return $this->hasMany(Device::class);
    }

    /**
     * Get user activities .
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activities() {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get user analytics
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function analytics() {
        return $this->hasMany(Analytic::class);
    }

    /**
     * Get user's profile .
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function profile() {
        return $this->belongsTo(Profile::class);
    }
    
    /**
     * My apps
     */
    public function myApps() {
        return $this->hasMany(MyApp::class);
    }

    /**
     * Get user categories .
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function categories() {
        return $this->hasMany(Category::class);
    }

    /** Get users in relation with current user . */
    public function employees() {
        return $this->belongsToMany(User::class, 'user_employee', 'user_id', 'employee_id')
            ->using(UserEmployee::class)
            ->withPivot(['id', 'status', 'name', 'daily_reports', 'weekly_reports', 'monthly_reports', 'send_a_copy_to_employee', 'disabled'])
            ->withTimestamps();
    }

    /** Get user employers */
    public function employers() {
        return $this->belongsToMany(User::class, 'user_employee', 'employee_id', 'user_id')
            ->using(UserEmployee::class)
            ->withPivot(['id', 'status', 'name', 'daily_reports', 'weekly_reports', 'monthly_reports', 'send_a_copy_to_employee', 'disabled'])
            ->withTimestamps();
    }
    
    /**
     * Get user top apps
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function topApps() {
        return $this->hasMany(TopApp::class);
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName() {
        return 'slug';
    }

}
