<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopApp extends Model {

    use HasFactory;

    protected $table = 'top_apps';

    protected $fillable = ['project_id', 'user_id', 'category_id', 'last_index', 'app', 'duration' ];

    public $timestamps = true;

    public $casts = [
        'app' => 'string',
        'duration' => 'integer',
    ];

    public $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * Project
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project() {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get category instance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category() {
        return $this->belongsTo(Category::class);
    }

}
