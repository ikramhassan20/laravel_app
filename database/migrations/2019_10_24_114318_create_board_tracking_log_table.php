<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBoardTrackingLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('board_tracking_log', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('board_tracking_id');
            $table->string('status');
            $table->text('message');
            $table->timestamps();
//            $table->foreign('board_tracking_id')->references('id')
//                ->on('board_tracking')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('board_tracking_log');
    }
}
