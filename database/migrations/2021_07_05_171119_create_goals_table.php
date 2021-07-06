<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoalsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('title');
            $table->text('description')->nullable()->default(null);
            $table->enum('tracking', ['productive_secs', 'neutral_secs', 'non_productive_secs',]);
            $table->bigInteger('user_id')->unsigned()->nullable()->default(null);
            $table->integer('team_id')->unsigned()->nullable()->default(null);
            $table->integer('value');
            $table->boolean('active')->default(true);
            $table->json('options');
            $table->date('due_date');

            $table->timestamps();

            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->onDelete('cascade')
                ->onUpdate('cascade');
                
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('goals');
    }
}
