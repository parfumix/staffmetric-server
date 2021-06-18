<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Table is used for collects general and user(employer) categories 
 * 
 */

class Category extends Model {
    
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = ['user_id', 'title', 'productivity'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function apps() {
        return $this->hasMany(App::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function myApps() {
        return $this->hasMany(MyApp::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users() {
        return $this->morphedByMany(User::class , 'productivity');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function profiles() {
        return $this->morphedByMany(Profile::class, 'productivity');
    }

    /**
     * Get category owner
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * Select categories by user .
     *
     * @param $user_id
     * @return mixed
     */
    public function byUser($user_id) {
        return $this->where('user_id', $user_id)->orWhereNull('user_id')->get();
    }

    /**
     * Select all categories by user
     *
     * @param array $users
     * @return mixed
     */
    public function byUsers(array $users) {
        return $this->whereIn('user_id', $users)->orWhereNull('user_id')->get();
    }


    public function isProductive() {
        return $this->productivity == 'productive';
    }

    public function isNonProductive() {
        return $this->productivity == 'non-productive';
    }

    public function isNeutral() {
        return $this->productivity == 'neutral';
    }
}
