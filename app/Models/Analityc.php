<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Analityc extends Model {
    use HasFactory;

    protected $fillable = ['user_id', 'employer_id', 'device_id', 'last_index_id', 'last_index_idle', 'total_secs',
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
    public function user() {
        return $this->belongsTo(\App\User::class);
    }

    /**
     * Get employer instance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employer() {
        return $this->belongsTo(\App\User::class);
    }

    /**
     * Get project instance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device() {
        return $this->belongsTo(\App\Device::class);
    }

}
