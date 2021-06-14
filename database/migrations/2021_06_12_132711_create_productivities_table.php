<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductivitiesTable extends Migration {
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('productivities', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('productivity_id');
            $table->string('productivity_type');
            $table->bigInteger('category_id')->unsigned();
            $table->enum('productivity', ['productive', 'non-productive', 'neutral'])->default('neutral');
            $table->timestamps();

            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('productivities');
    }
}
