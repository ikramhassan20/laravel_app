<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBoardFilterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('board_filter', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('board_id');
            $table->text('criteria');
            $table->text('rules');
            $table->enum('filter_type', ['conversion', 'action']);
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
        Schema::dropIfExists('board_filter');
    }
}
