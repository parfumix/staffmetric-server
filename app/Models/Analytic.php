<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Analytic extends Model {
    use HasFactory;

    protected $fillable = ['last_index_id', 'project_id', 'device_id', 'user_id', 'employer_id', 'total_secs',
        'productive_secs', 'neutral_secs', 'non_productive_secs', 'idle_secs', 'idle_count',
        'email_secs', 'office_secs', 'overtime_secs', 'meetings_secs', 'social_network_secs', 'app_usage', 'web_usage', 'employee_time'];

    public $timestamps = true;

    public $dates = [
        'employee_time',
        'created_at',
        'updated_at',
    ];

    public $casts = [
        'total_secs' => 'integer',
        'productive_secs' => 'integer',
        'neutral_secs' => 'integer',
        'non_productive_secs' => 'integer',
        'idle_secs' => 'integer',
        'idle_count' => 'integer',
        'email_secs' => 'integer',
        'office_secs' => 'integer',
        'overtime_secs' => 'integer',
        'meetings_secs' => 'integer',
        'social_network_secs' => 'integer',
        'app_usage' => 'integer',
        'web_usage' => 'integer',
    ];

    /**
     * Get user instance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project() {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get project instance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device() {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get user instance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * Get employer instance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employer() {
        return $this->belongsTo(User::class);
    }

}
