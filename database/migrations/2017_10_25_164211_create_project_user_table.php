<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectUserTable extends Migration {
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('project_user', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('project_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('team_id')->unsigned()->nullable()->default(null);
            $table->string('limit_hours')->nullable()->default(null);
            $table->decimal('hourly_rate', 10, 2)->nullable()->default(null);
            $table->enum('role', ['administrator', 'manager', 'member'])->default('member');
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);

            $table->foreign('team_id')
                ->references('id')->on('teams')
                ->onDelete('set null');

            $table->foreign('project_id')
                ->references('id')->on('projects')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('project_user');
    }
}
