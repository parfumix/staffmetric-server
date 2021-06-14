<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model {

    use HasFactory;

    protected $fillable = ['device_id', 'user_id', 'user_id', 'app', 'duration', 'full_url', 'start_at', 'end_at'];

    public $timestamps = true;

    public $dates = [
        'start_at',
        'end_at',
        'created_at',
        'end_at',
    ];

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
     * Get device instance .
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device() {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get category .
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category() {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get user .
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * Is application current activity
     *
     * @return bool
     */
    public function isApp() {
        return empty($this->full_url);
    }

    /**
     * Is url current activity
     *
     * @return bool
     */
    public function isUrl() {
        return !$this->isApp();
    }

}
