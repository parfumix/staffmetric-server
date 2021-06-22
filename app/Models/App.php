<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * Table is used for collecto general and user(employer) apps 
 *  when reportsService analyzie productivity it checks app category productivity by using this table
 * 
 */

class App extends Model 
    implements Sortable {

    use HasFactory;

    use SortableTrait;

    public $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    protected $fillable = ['name', 'user_id', 'profile_id', 'category_id'];

     /**
     * Make Sortable
     * 
     */
    public function buildSortQuery() {
        return static::query()->where('user_id', $this->user_id);
    }
    
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
