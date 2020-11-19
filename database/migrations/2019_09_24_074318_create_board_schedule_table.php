<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBoardScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('board_schedule', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('board_id');
            $table->enum('day', ['monday', 'tuesday','wednesday','thursday','friday','saturday','sunday']);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('board_id')->references('id')->on('board')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('board_schedule');
    }
}
