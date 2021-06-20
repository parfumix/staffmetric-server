<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutomationsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->bigInteger('user_id')->unsigned()->nullable()->default(null);
            $table->bigInteger('employer_id')->unsigned();
            $table->boolean('active')->default(true);
            $table->json('trigger');
            $table->json('actions');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('employer_id')
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
        Schema::dropIfExists('automations');
    }
}
