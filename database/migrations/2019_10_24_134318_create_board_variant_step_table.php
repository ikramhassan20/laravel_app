<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBoardVariantStepTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('board_variant_step', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('variant_id');
          //  $table->enum('variant_step_id', ['1', '2', '3', '4', '5']);
            $table->unsignedInteger('message_type_id')->nullable();
            $table->unsignedInteger('orientation_id')->nullable();
            $table->unsignedInteger('position_id')->nullable();
            $table->unsignedInteger('platform_id')->nullable();
            $table->string('from_email', 100)->nullable();
            $table->string('subject', 255)->nullable();
            $table->unsignedInteger('step_control_delay_value')->default(1);
            $table->enum('step_control_delay_unit', ['day', 'week', 'month','minute'])->default('day');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->date('start_date');
            $table->integer('board_delay')->default('0');
            $table->integer('variant_step_number')->default('1');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('variant_id')->references('id')->on('board_variant')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index('message_type_id', 'BOARD_VARIANT_STEP_MESSAGE_TYPE_ID_INDEX');
            $table->index('orientation_id', 'BOARD_VARIANT_STEP_ORIENTATION_ID_INDEX');
            $table->index('position_id', 'BOARD_VARIANT_STEP_POSITION_ID_INDEX');
            $table->index('platform_id', 'BOARD_VARIANT_STEP_PLATFORM_ID_INDEX');
            $table->index('status', 'BOARD_VARIANT_STEP_STATUS_INDEX');
            //$table->index('start_date', 'BOARD_VARIANT_STEP_START_DATE_INDEX');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('board_variant_step');
    }
}
