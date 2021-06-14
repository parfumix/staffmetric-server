<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserEmployeeTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('user_employee', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('employee_id')->unsigned();
            $table->enum('status', ['sent', 'accepted', 'rejected'])->default('sent');
            $table->string('name')->nullable()->default(null);
            $table->decimal('hourly_rate', 10, 2)->nullable()->default(null);
            $table->boolean('daily_reports')->default(0);
            $table->boolean('weekly_reports')->default(0);
            $table->boolean('monthly_reports')->default(0);
            $table->boolean('send_a_copy_to_employee')->default(0);

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('employee_id')->references('id')->on('users')
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
        Schema::dropIfExists('user_employee');
    }
}
