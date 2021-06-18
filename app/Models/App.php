<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Table is used for collecto general and user(employer) apps 
 *  when reportsService analyzie productivity it checks app category productivity by using this table
 * 
 */

class App extends Model {
    use HasFactory;

    protected $fillable = ['name', 'user_id', 'profile_id', 'category_id'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function profile() {
        return $this->belongsTo(Profile::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }
}
