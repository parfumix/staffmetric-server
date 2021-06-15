<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MyApp extends Model {
    use HasFactory;

    use SoftDeletes;

    protected $table = 'my_apps';

    protected $fillable = ['name', 'user_id', 'category_id'];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }
}
