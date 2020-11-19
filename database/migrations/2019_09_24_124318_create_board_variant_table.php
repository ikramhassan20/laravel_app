<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBoardVariantTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('board_variant', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('variant_type', ['Email', 'InApp', 'Push'])->default('Push');
            $table->unsignedInteger('board_id');
            // $table->unsignedInteger('variant_id');
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('subject')->nullable();
            $table->tinyInteger('distribution')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('board_id')->references('id')->on('board')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index('variant_type', 'BOARD_VARIANT_VARIANT_TYPE_INDEX');
            $table->index('distribution', 'BOARD_VARIANT_DISTRIBUTION_INDEX');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('board_variant');
    }
}
