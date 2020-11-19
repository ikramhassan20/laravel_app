<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBoardQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('board_queue', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('board_id');
            $table->enum('status', ['Available', 'Processing','Complete','Failed']);
            $table->enum('priority', ['1', '2','3']);
            $table->text('details')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('start_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('board_id')->references('id')
                ->on('board')->onUpdate('cascade')->onDelete('cascade');

            $table->index('status', 'BOARD_QUEUE_STATUS_INDEX');
            $table->index('start_at', 'BOARD_QUEUE_START_AT_INDEX');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('board_queue');
    }
}
