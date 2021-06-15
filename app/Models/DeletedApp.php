<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeletedApp extends Model {
    use HasFactory;

    protected $table = 'deleted_apps';

    protected $fillable = ['name'];

    public $timestamps;

    /**
     * Get the user instance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
    }
}
