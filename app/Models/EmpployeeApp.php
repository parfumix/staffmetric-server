<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Table is used for employee-employer productivity
 * 
 */

class EmpployeeApp extends Model {
    use HasFactory;

    protected $table = 'employee_apps';

    protected $fillable = ['name', 'user_id', 'employee_id', 'category_id'];

    public $timestamps = true;

    public function employee() {
        return $this->belongsTo(User::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }
}
