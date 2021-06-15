<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Presenters\TeamPresenter;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Database\Eloquent\Model;

class Team extends Model {

    use HasFactory;

    use Sluggable;

    use SluggableScopeHelpers;

    protected $table = 'teams';

    protected $fillable = ['user_id', 'title', 'description', 'daily_reports', 'weekly_reports', 'monthly_reports', 'timezone'];

    public $timestamps = true;

    protected $presenter = TeamPresenter::class;

    public function sluggable(): array {
        return [
            'slug' => [
                'source' => ['user.name', 'title']
            ]
        ];
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function users() {
        return $this->belongsToMany(User::class)
            ->using(TeamUser::class)
            ->withPivot(['id', 'status', 'role'])
            ->withTimestamps();
    }

    public function administrators() {
        return $this->users()->where('role', 'administrator')->get();
    }

    public function getTeamUserAuthenticated() {
        return $this->users()->where('user_id', \auth()->user()->id)->first();
    }

    public function acceptedUsers($excludeOwner = false) {
        $query = $this->users()->where('status', 'accepted');

        if( $excludeOwner )
            $query->whereNotIn('user_id', [$this->user_id]);

        return $query;
    }

    public function projects() {
        return $this->hasMany(Project::class);
    }

    public function tasks() {
        return $this->hasMany(Task::class);
    }

    public function boards() {
        return $this->hasMany(Board::class);
    }

    public function activities() {
        return $this->hasMany(TeamActivity::class);
    }


    public function addActivity($message, array $payload = []) {
        return $this->activities()->create(['user_id' => \auth()->user()->id ?? null, 'message' => $message, 'payload' => $payload]);
    }


    public static function getAvailablePermissions($keys = false) {
        $data = collect([
            'apps' => ['description' => trans('Allow administrator view your application activity')],
            'productivity' => ['description' => trans('Allow administrator view your productivity activity')]
        ]);

        return $keys
            ? $data->keys()
            : $data;
    }

    public function isAuthenticatedOwner() {
        return $this->user_id == \auth()->user()->id;
    }

    public function isOwner(User $user) {
        return $user->id == $this->user_id;
    }


    public function todayTasks() {
        return $this->tasks()
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->orderBy('created_at', 'desc');
    }

    public function weeklyTasks(\Carbon\Carbon $end_date = null) {
        if(! $end_date) $end_date = now();

        return $this->tasks()
            ->whereDate('created_at', '>=', $end_date->copy()->subWeek()->format('Y-m-d'))
            ->whereDate('created_at', '<=', $end_date->format('Y-m-d'))
            ->orderBy('created_at', 'desc');
    }

    public function monthlyTasks(\Carbon\Carbon $end_date = null) {
        if(! $end_date) $end_date = now();

        return $this->tasks()
            ->whereDate('created_at', '>=', $end_date->copy()->subMonth()->format('Y-m-d'))
            ->whereDate('created_at', '<=', $end_date->format('Y-m-d'))
            ->orderBy('created_at', 'desc');
    }

    public function yearlyTasks(\Carbon\Carbon $end_date = null) {
        if(! $end_date) $end_date = now();

        return $this->tasks()
            ->whereDate('created_at', '>=', $end_date->copy()->subYear()->format('Y-m-d'))
            ->whereDate('created_at', '<=', $end_date->format('Y-m-d'))
            ->orderBy('created_at', 'desc');
    }


    public function todayClosedTasks() {
        return $this->tasks()
            ->whereDate('completed_at', now()->format('Y-m-d'))
            ->orderBy('created_at', 'desc');
    }


    public function enableDailyReports($state = true) {
        return $this->daily_reports = $state ? 1 : 0;
    }

    public function enableWeeklyReports($state = true) {
        return $this->weekly_reports = $state ? 1 : 0;
    }

    public function enableMonthlyReports($state = true) {
        return $this->monthly_reports = $state ? 1 : 0;
    }

}
