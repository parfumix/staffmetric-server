<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Mpociot\Teamwork\TeamworkTeam;

class Team extends TeamworkTeam {
    use HasFactory;

    /**
     * Get goals instance
     * 
     */
    public function goals() {
        return $this->hasMany(Goal::class);
    }
}
