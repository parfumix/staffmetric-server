<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;

class Goal extends Model {
    use HasFactory;

    /**
     * Fillable attributes
     * 
     */
    protected $fillable = ['uuid', 'title', 'description', 'tracking', 'user_id', 'team_id', 'value', 'due_date', 'options', 'active'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
        'options' => 'array',
    ];

    protected static function boot() {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Uuid::generate(5, time(), Uuid::NS_DNS)->string;
            }
        });
    }

    /**
     * Get device by uuid
     */
    public function scopeOfUuid($query, $uuid) {
        return $query->where('uuid', $uuid);
    }

    /**
     * Get user instance
     * 
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * Get team instance
     * 
     */
    public function team() {
        return $this->belongsTo(Team::class);
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
