<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnalyticsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('analytics', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('last_index_id')->unsigned()->nullable()->default(null);
            $table->integer('last_index_idle')->unsigned()->nullable()->default(null);
            $table->bigInteger('device_id')->unsigned()->nullable()->default(null);
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('employer_id')->unsigned()->nullable()->default(null);
            $table->integer('total_secs')->nullable()->default(null);
            $table->integer('productive_secs')->nullable()->default(null);
            $table->integer('neutral_secs')->nullable()->default(null);
            $table->integer('non_productive_secs')->nullable()->default(null);
            $table->integer('idle_secs')->nullable()->default(null);
            $table->integer('idle_count')->nullable()->default(null);
            $table->integer('email_secs')->nullable()->default(null);
            $table->integer('office_secs')->nullable()->default(null);
            $table->integer('overtime_secs')->nullable()->default(null);
            $table->integer('meetings_secs')->nullable()->default(null);
            $table->integer('social_network_secs')->nullable()->default(null);
            $table->integer('app_usage')->nullable()->default(null);
            $table->integer('web_usage')->nullable()->default(null);
            $table->timestamp('employee_time')->nullable();
            $table->timestamps();

            $table->foreign('employer_id')
                ->references('id')->on('users')
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
        Schema::dropIfExists('analytics');
    }
}
