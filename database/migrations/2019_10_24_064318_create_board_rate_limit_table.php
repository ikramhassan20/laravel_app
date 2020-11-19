<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBoardRateLimitTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('board_rate_limit', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('board_id');
            $table->integer('rate_limit');
            $table->integer('duration_value');
            $table->enum('duration_unit', config('enums.migration.ratelimit.duration_unit'));
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
        Schema::dropIfExists('board_rate_limit');
    }
}
