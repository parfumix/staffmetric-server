<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivitiesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('activities', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('project_id')->unsigned()->nullable()->default(null);
            $table->bigInteger('device_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('category_id')->unsigned()->nullable()->default(null);
            $table->string('app')->nullable()->default(null);
            $table->integer('duration')->nullable()->default(null);
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->timestamps();

            $table->index(['app']);

            $table->foreign('project_id')
                ->references('id')->on('projects')
                ->onDelete('cascade');
                
            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('device_id')
                ->references('id')->on('devices')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('activities');
    }
}
