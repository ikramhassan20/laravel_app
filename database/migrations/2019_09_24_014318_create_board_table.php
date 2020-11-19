<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBoardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('board', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('app_group_id');
            $table->text('tags')->nullable();
            $table->string('code', 100)->nullable();
            $table->string('name', 255)->nullable();
            $table->enum('step', ['general', 'delivery', 'target', 'setting', 'preview'])->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->enum('status', ['active', 'draft', 'expired', 'suspended'])->default('draft');
            $table->boolean('is_remove_cache')->default(false);
            $table->enum('schedule_type', ['daily', 'weekly', 'once'])->nullable();
            $table->enum('delivery_type', ['schedule', 'action', 'api'])->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->integer('action_trigger_delay_value')->nullable();
            $table->enum('action_trigger_delay_unit', ['second', 'minute', 'hour'])->nullable();
            $table->tinyInteger('delivery_control')->default(0);
            $table->integer('delivery_control_delay_value')->nullable();
            $table->enum('delivery_control_delay_unit', ['minute', 'day', 'week', 'month'])->nullable();
            $table->tinyInteger('capping')->default(0);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('app_group_id')->references('id')->on('app_group')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index('code', 'BOARD_CODE_INDEX');
            $table->index('start_time', 'BOARD_START_TIME_INDEX');
            $table->index('status', 'BOARD_STATUS_INDEX');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('board');
    }
}
