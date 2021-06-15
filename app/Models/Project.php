<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Database\Eloquent\Model;

class Project extends Model {

    use HasFactory;

    use Sluggable;

    use SluggableScopeHelpers;

    const ADMINISTRATOR = 'administrator';
    const MANAGER = 'manager';
    const MEMBER = 'member';

    protected $table = 'projects';

    protected $fillable = ['remote_id', 'user_id', 'team_id', 'title', 'description', 'is_private', 'is_billable', 'time_budget', 'money_budget', 'budget_activated', 'deadline_at'];

    public $timestamps = true;

    public $casts = [
        'is_private' => 'boolean',
        'is_billable' => 'boolean',
    ];

    public $dates = [
        'created_at',
        'created_at',
        'deadline_at',
    ];

    public function sluggable(): array{
        return [
            'slug' => [
                'source' => ['user.name', 'title'],
            ],
        ];
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function team() {
        return $this->belongsTo(Team::class);
    }

    public function users() {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id')
            ->using(ProjectUser::class)
            ->withPivot('hourly_rate', 'role', 'limit_hours', 'team_id')
            ->withTimestamps();
    }

    public function invoices() {
        return $this->hasMany(Invoice::class);
    }

    public function analytics() {
        return $this->hasMany(Analytic::class);
    }

    public function getTotalSpent() {
        $total = 0;
        $total_secs = $this->analytics->sum('office_secs');
        foreach ($this->employees()->with('employers')->get() as $employee) {
            if (!count($employee->employers)) {
                continue;
            }

            $employeer = null;
            foreach ($employee->employers as $item) {
                if ($item->id == \request()->user()->id) {
                    $employeer = $item;
                    break;
                }
            }

            if ($employeer) {
                $total += $employee->pivot->getMoneySpent($total_secs, $employeer->pivot->getHourlyRate());
            }
        }

        return $total;
    }

    public function isAuthenticatedOwner() {
        return $this->user_id == \auth()->user()->id;
    }

    public function isOwner(User $user) {
        return $user->id == $this->user_id;
    }

    public function isPrivate() {
        return $this->is_private;
    }

    public function isBillable() {
        return $this->is_billable;
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName() {
        return 'slug';
    }
}
