<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration {
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('categories', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->string('title');
            $table->enum('productivity', ['productive', 'neutral', 'non-productive'])->default('neutral');
            $table->smallInteger('order_column')->nullable();
            $table->timestamps();

            $table->index(['title', 'productivity']);

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
        Schema::dropIfExists('categories');
    }
}
