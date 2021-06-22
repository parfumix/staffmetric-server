<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('projects', function (Blueprint $table) {
            $table->id('id');
            $table->string('title');
            $table->string('slug');
            
            $table->string('remote_id')->nullable()->default(null);
            $table->bigInteger('user_id')->unsigned();
            $table->boolean('is_private')->default(0);
            $table->boolean('is_billable')->default(0);
            $table->integer('time_budget')->default(0);
            $table->float('money_budget')->default(0);
            $table->enum('budget_activated', ['time', 'money'])->nullable()->default(null);
       
            $table->text('description')->nullable()->default(null);
            $table->date('deadline_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('projects');
    }
}
