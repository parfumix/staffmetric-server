<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeviceTable extends Migration {
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('devices', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('user_id')->unsigned();
            $table->uuid('uuid');
            $table->string('name');
            $table->string('slug');
            $table->string('os')->nullable()->default(null);
            $table->softDeletes();
            $table->timestamp('last_update_at', 0)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'uuid']);

            $table->index(['uuid', 'user_id']);

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
        Schema::dropIfExists('devices');
    }
}
