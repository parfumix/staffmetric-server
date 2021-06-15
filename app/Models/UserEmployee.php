<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserEmployee extends Pivot {

    protected $table = 'user_employee';

    protected $fillable = ['status', 'name', 'daily_reports', 'weekly_reports', 'monthly_reports', 'send_a_copy_to_employee', 'disabled'];

    public $timestamps = true;

    public $dates = [
        'created_at',
        'updated_at',
    ];

    public $casts = [
        'disabled' => 'boolean',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function employee() {
        return $this->belongsTo(User::class);
    }
  
    public function isSent() {
        return $this->status == 'sent';
    }

    public function isAccepted() {
        return $this->status == 'accepted';
    }

    public function isRejected() {
        return $this->status == 'rejected';
    }

    public function isDisabled() {
        return $this->disabled;
    }
}
