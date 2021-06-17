<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workspace extends Model {

    use HasFactory;

    protected $table = 'worksapces';

    protected $fillable = ['user_id', 'title', 'description', ];

    public $timestamps = true;

    /**
     * Get user instance
     * 
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

}
