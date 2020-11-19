<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBoardSegmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('board_segment', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('board_id');
            $table->unsignedInteger('segment_id');
            $table->timestamps();

            $table->foreign('board_id')->references('id')->on('board')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('segment_id')->references('id')->on('segment')
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
        Schema::dropIfExists('board_segment');
    }
}
