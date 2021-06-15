<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamUserTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('team_user', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('team_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();

            $table->enum('status', ['sent', 'accepted', 'rejected'])->default('sent');
            $table->enum('role', ['administrator', 'manager', 'member'])->default('member');

            $table->timestamps();

            $table->unique(['team_id', 'user_id']);

            $table->foreign('team_id')->references('id')->on('teams')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('team_user');
    }
}
