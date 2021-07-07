<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Mpociot\Teamwork\TeamworkTeam;

class Team extends TeamworkTeam {
    use HasFactory;

    /**
     * @var array
     */
    protected $fillable = ['name', 'owner_id', 'type'];

    const TYPE_TEAM = 'team';
    const TYPE_WORKSPACE = 'workspace';

    /**
     * Get goals instance
     * 
     */
    public function goals() {
        return $this->hasMany(Goal::class);
    }

    /**
     * Check if currently is workspace
     * 
     */
    public function isWorkspace() {
        return $this->type == self::TYPE_WORKSPACE;
    }

    /**
     * Check if currently is team
     * 
     */
    public function isTeam() {
        return $this->type == self::TYPE_TEAM;
    }

}
