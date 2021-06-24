<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Webpatser\Uuid\Uuid;

class Device extends Model {
    use HasFactory;

    use SoftDeletes;

    use Sluggable;

    use SluggableScopeHelpers;

    const DEVICE_OFFLINE_SECONDS = 900;

    protected $table = 'devices';

    protected $fillable = ['user_id', 'uuid', 'name', 'os', 'last_update_at'];

    public $timestamps = true;

    protected $dates = [
        'last_update_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected static function boot() {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Uuid::generate(5, time(), Uuid::NS_DNS)->string;
            }
        });
    }

    public function sluggable(): array{
        return [
            'slug' => [
                'source' => ['user.name', 'name'],
            ],
        ];
    }

    /**
     * Adding authenticated scope .
     *
     * @param $query
     * @return mixed
     */
    public function scopeAuthenticated($query) {
        return $query->where('user_id', \Auth::id());
    }

    /**
     * Get device by uuid
     */
    public function scopeOfUuid($query, $uuid) {
        return $query->where('uuid', $uuid);
    }

    /**
     * Get device activities ..
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activities() {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get device analytics ..
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function analytics() {
        return $this->hasMany(Analytic::class);
    }

    /**
     * Get browse owner
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * Is owner user
     *
     * @param User $user
     * @return mixed
     */
    public function isOwner(User $user) {
        return $user->id = $this->user_id;
    }

    /**
     * Get device os
     *
     * @return mixed
     */
    public function getOs() {
        return $this->os;
    }

    /**
     * Check wether device is offline
     * 
     */
    public function isOnline($idle_seconds = self::DEVICE_OFFLINE_SECONDS) {
        if(!$this->last_update_at) return false;
        $seconds_from_last_activity = now()->diffInSeconds($this->last_update_at);

        if(is_null($seconds_from_last_activity) || ! $seconds_from_last_activity)
            return false;

        return $seconds_from_last_activity <= $idle_seconds;
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName() {
        return 'uuid';
    }

}
