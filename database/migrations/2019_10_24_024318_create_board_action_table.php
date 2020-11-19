<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBoardActionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('board_action', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('board_id');
            $table->unsignedInteger('action_id');
            $table->string('value', 250)->nullable();
            $table->enum('action_type', ['trigger','conversion']);
            $table->enum('period', ['minute','hour','day'])->default('day');
            $table->tinyInteger('validity')->default(0);
            // $table->enum('period', ['minute', 'hour', 'day'])->default('day');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('board_id')->references('id')
                ->on('board')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('board_action');
    }
}
