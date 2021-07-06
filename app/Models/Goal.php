<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model {
    use HasFactory;

    protected $fillable = ['title', 'description', 'tracking', 'user_id', 'team_id', 'value', 'due_date'];

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

}
