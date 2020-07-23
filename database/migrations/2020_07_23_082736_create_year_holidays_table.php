<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYearHolidaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('year_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('day');
            $table->integer('month');
            $table->integer('day_of_week');
            $table->foreignId('year_id')->constrained('years')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('year_holidays');
    }
}
